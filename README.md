<div class="filament-hidden">

![Laravel Satis](https://raw.githubusercontent.com/jeffersongoncalves/laravel-satis/main/art/jeffersongoncalves-laravel-satis.png)

</div>

# Laravel Satis

A Laravel package for managing private Composer repositories powered by [Satis](https://github.com/composer/satis).

## Features

- **Package Management** — Add and manage Composer & GitHub package sources
- **Token-Based Auth** — Secure access with per-token package scoping
- **Automated Builds** — Queue-driven Satis builds with configurable scheduling
- **GitHub Webhooks** — Auto-rebuild on push, release and create events with signature verification
- **Download Tracking** — Per-version download statistics
- **Dependency Tracking** — Public/private dependency classification with automatic processing
- **Multi-Tenancy** — Tenant-isolated data with configurable resolver
- **Credential Validation** — Verify package accessibility before building
- **Intelligent Validation** — Timestamp-based comparison to skip unnecessary rebuilds
- **Auth.json Support** — Automatic auth.json generation for authenticated Composer builds
- **Credential Sanitization** — Remove transport-options from Satis JSON files to prevent credential leaks
- **Dev Packages** — Mark packages as development-only with `is_dev` flag
- **Composer V2 Protocol** — Full support for `packages.json`, `p2/` and include files

## Requirements

- PHP 8.1+
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

### Managing Packages Programmatically

```php
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

// Create a package
$packageModel = ModelResolver::package();
$package = $packageModel::create([
    'name' => 'vendor/package-name',
    'type' => 'composer',
    'url' => 'https://repo.example.com',
    'username' => 'user',
    'password' => 'secret',
]);

// Create a GitHub package
$githubPackage = $packageModel::create([
    'name' => 'vendor/github-package',
    'type' => 'github',
    'url' => 'https://github.com/vendor/repo.git',
    'username' => 'github-user',
    'password' => 'github-token',
]);

// Create a dev package
$devPackage = $packageModel::create([
    'name' => 'vendor/dev-tool',
    'type' => 'composer',
    'url' => 'https://repo.example.com',
    'is_dev' => true,
]);

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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
