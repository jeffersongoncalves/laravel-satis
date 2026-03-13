<div class="filament-hidden">

![Laravel Satis](https://raw.githubusercontent.com/jeffersongoncalves/laravel-satis/main/art/jeffersongoncalves-laravel-satis.png)

</div>

# Laravel Satis

A Laravel package for managing private Composer repositories powered by [Satis](https://github.com/composer/satis).

## Features

- **Credential Management** — Dedicated Credential model for centralized authentication (URL, email, password)
- **Package Management** — Add and manage Composer & GitHub package sources linked to credentials
- **Token-Based Auth** — Secure access with per-token package scoping
- **Automated Builds** — Queue-driven Satis builds with configurable scheduling
- **Credential Grouping** — Separate builds per credential with snapshot merging
- **Inline Auth URLs** — RFC 3986 percent-encoded credentials in repository URLs
- **Rate-Limit Retry** — Exponential backoff on HTTP 429 responses during builds
- **GitHub Webhooks** — Auto-rebuild on push, release and create events with signature verification
- **Download Tracking** — Per-version download statistics
- **Dependency Tracking** — Public/private dependency classification with automatic processing
- **Multi-Tenancy** — Tenant-isolated data with configurable resolver
- **Credential Validation** — Verify package and credential accessibility before building
- **Intelligent Validation** — Timestamp-based comparison to skip unnecessary rebuilds
- **Credential Sanitization** — Remove transport-options and inline credentials from Satis JSON files
- **Dev Packages** — Mark packages as development-only with `is_dev` flag
- **Composer V2 Protocol** — Full support for `packages.json`, `p2/` and include files

## Requirements

- PHP 8.2+
- Laravel 10+
- Satis (`composer/satis` — included as dependency)

## Installation

```bash
composer require jeffersongoncalves/laravel-satis
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="satis-migrations"
php artisan migrate
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag="satis-config"
```

## Configuration

The config file (`config/satis.php`) covers:

### Multi-Tenancy

```php
'tenancy' => [
    'enabled' => false,
    'model' => null,
    'foreign_key' => null,
    'ownership_relationship' => null,
    'resolver' => null, // callable that returns the current tenant ID
],
```

The `resolver` accepts any callable that returns the current tenant ID. Example:

```php
// In a service provider or middleware
config(['satis.tenancy.enabled' => true]);
config(['satis.tenancy.model' => \App\Models\Team::class]);
config(['satis.tenancy.foreign_key' => 'team_id']);
config(['satis.tenancy.resolver' => fn () => auth()->user()?->current_team_id]);
```

### Table Prefix

```php
'table_prefix' => 'satis_',
```

Set to `null` to use table names without a prefix.

### Custom Models

Override any model to extend the default behavior:

```php
'models' => [
    'credential' => \App\Models\SatisCredential::class,
    'package' => \App\Models\SatisPackage::class,
    'token' => \App\Models\SatisToken::class,
    // ...
],
```

### Storage

```php
'storage_disk' => 'local',
'storage_path' => 'satis',
```

### Queue

```php
'queue' => [
    'connection' => null,  // null = default connection
    'queue_name' => null,  // null = default queue
    'timeout' => 86400,    // 24 hours (in seconds)
],
```

### Scheduling

```php
'schedule' => [
    'build' => 'weekly',        // any Laravel Schedule method or null
    'token_build' => 'weekly',
    'validate' => 'hourly',
    'sanitize' => 'daily',
    'dependencies' => 'weekly',
],
```

### Routes

```php
'routes' => [
    'api_prefix' => 'api/satis',
    'composer_prefix' => 'satis',
    'middleware' => ['api'],
],
```

## Usage

### Managing Credentials and Packages Programmatically

```php
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

// Create a credential
$credentialModel = ModelResolver::credential();
$credential = $credentialModel::create([
    'name' => 'My Private Repo',
    'url' => 'https://repo.example.com',
    'email' => 'user',
    'password' => 'secret',
]);

// Create a package using the credential
$packageModel = ModelResolver::package();
$package = $packageModel::create([
    'name' => 'vendor/package-name',
    'type' => 'composer',
    'credential_id' => $credential->id,
]);

// Create a GitHub credential and package
$githubCredential = $credentialModel::create([
    'name' => 'GitHub',
    'url' => 'https://github.com/vendor/repo.git',
    'email' => 'github-user',
    'password' => 'github-token',
]);

$githubPackage = $packageModel::create([
    'name' => 'vendor/github-package',
    'type' => 'github',
    'credential_id' => $githubCredential->id,
]);

// Create a dev package (reusing same credential)
$devPackage = $packageModel::create([
    'name' => 'vendor/dev-tool',
    'type' => 'composer',
    'credential_id' => $credential->id,
    'is_dev' => true,
]);

// Validate a credential
$result = app(\JeffersonGoncalves\LaravelSatis\Actions\ValidateCredential::class)
    ->execute($credential);
// $result = ['success' => true, 'message' => 'Credential validated successfully.']

// Create a token
$tokenModel = ModelResolver::token();
$token = $tokenModel::create([
    'name' => 'My Token',
    'email' => 'user@example.com',
]);

// Assign packages to token
$token->packages()->attach($package->id);
```

### Running Builds

```bash
# Build all packages (tenant-based)
php artisan satis:build

# Build for a specific tenant
php artisan satis:build --tenant=1

# Build per token (all tokens with packages)
php artisan satis:token-build

# Build for a specific token
php artisan satis:token-build --token=5

# Validate credentials and trigger rebuilds if needed
php artisan satis:validate

# Process dependencies
php artisan dependency:packages

# Remove credentials from Satis JSON files
php artisan satis:sanitize

# Clean all Satis builds from storage
php artisan satis:clean

# Force clean without confirmation
php artisan satis:clean --force
```

### Composer Client Configuration

After building, clients can use your private repository:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://your-app.com/satis"
        }
    ]
}
```

Authenticate using the token as a password with any username:

```bash
composer config http-basic.your-app.com/satis "any-username" "your-token-here"
```

### GitHub Webhooks

Each package gets a unique reference for webhook URLs:

```
POST /api/satis/webhooks/github/{package-reference}
```

Set the **Content type** to `application/json` and optionally configure a **Secret** using the package's `webhook_secret`.

**Supported events:** `push`, `release`, `create` — all other events are ignored with HTTP 200.

The webhook handler:
1. Validates the package is a GitHub type (returns 400 otherwise)
2. Filters supported events
3. Verifies HMAC-SHA256 signature when a secret is configured
4. Dispatches `SyncTenantPackages` for the tenant rebuild
5. Dispatches `SyncTokenPackages` for each token associated with the package

## API Endpoints

### Composer Protocol (requires token auth)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/satis/packages.json` | Root packages file |
| `GET` | `/satis/include/{include}.json` | Include files |
| `GET` | `/satis/p2/{vendor}/{package}.json` | V2 protocol metadata |
| `GET` | `/satis/archives/{vendor}/{package}/{file}` | Package archives |

### API

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/satis/composer/downloads` | Download notifications |
| `POST` | `/api/satis/webhooks/github/{reference}` | GitHub webhook |

## Commands

| Command | Description |
|---------|-------------|
| `satis:build` | Build Satis repository (tenant-based) |
| `satis:token-build` | Build Satis repository (token-based) |
| `satis:validate` | Validate package credentials and trigger rebuilds if needed |
| `satis:clean` | Clean all Satis builds from storage |
| `satis:sanitize` | Remove credentials from Satis JSON files |
| `dependency:packages` | Process and sync package dependencies |

## Upgrading from v1.x to v2.0

### Breaking Changes

1. **Credential model**: Credentials are now stored in a dedicated `credentials` table instead of directly on the `packages` table.

2. **Package model**: The `url`, `username`, and `password` columns have been removed. Packages now reference a `credential_id` foreign key (required).

3. **CreateAuthJson removed**: Authentication is now handled via inline auth URLs (RFC 3986) instead of a separate auth.json file.

4. **Code lengths**: `webhook_secret` changed from 40 to 64 characters, `reference` from 20 to 32 characters.

### Migration Steps

1. Update your dependency:

```bash
composer require jeffersongoncalves/laravel-satis:^2.0
```

2. Publish and run the new migrations:

```bash
php artisan vendor:publish --tag="satis-migrations"
```

3. **Before running migrations**, migrate existing data to the credentials table:

```php
use JeffersonGoncalves\LaravelSatis\Models\Credential;

$packages = DB::table('satis_packages')->get();
$credentialMap = [];

foreach ($packages as $package) {
    $key = $package->url . '|' . $package->username;

    if (! isset($credentialMap[$key])) {
        $credential = Credential::create([
            'name' => parse_url($package->url, PHP_URL_HOST) ?? $package->url,
            'url' => $package->url,
            'email' => $package->username,
            'password' => $package->password,
            'is_validated' => $package->is_credentials_validated,
            'validated_at' => $package->credentials_validated_at,
        ]);
        $credentialMap[$key] = $credential->id;
    }

    DB::table('satis_packages')
        ->where('id', $package->id)
        ->update(['credential_id' => $credentialMap[$key]]);
}
```

4. Run migrations:

```bash
php artisan migrate
```

5. Update code references:
   - `$package->url` → `$package->credential->url`
   - `$package->username` → `$package->credential->email`
   - `$package->password` → `$package->credential->password`
   - `CreateAuthJson` → removed, no longer needed

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
