# Laravel Satis

A Laravel package for managing private Composer repositories powered by [Satis](https://github.com/composer/satis).

## Features

- **Package Management** — Add and manage Composer & GitHub package sources
- **Token-Based Auth** — Secure access with per-token package scoping
- **Automated Builds** — Queue-driven Satis builds with configurable scheduling
- **GitHub Webhooks** — Auto-rebuild on push events
- **Download Tracking** — Per-version download statistics
- **Dependency Tracking** — Public/private dependency classification
- **Multi-Tenancy** — Tenant-isolated data with configurable resolver
- **Credential Validation** — Verify package accessibility before building
- **Composer V2 Protocol** — Full support for `packages.json`, `p2/` and include files

## Requirements

- PHP 8.1+
- Laravel 10+
- Satis (`composer/satis` — included as dependency)

## Installation

```bash
composer require jeffersongoncalves/laravel-satis
```

Run the install command to publish the `satis.json` to your project root:

```bash
php artisan satis:install
```

The command will ask you for:
- **Repository name** — e.g. `my-company/packages`
- **Homepage URL** — e.g. `https://packages.example.com`

You can also pass them directly:

```bash
php artisan satis:install --name="my-company/packages" --homepage="https://packages.example.com"
```

Use `--force` to overwrite an existing `satis.json`:

```bash
php artisan satis:install --force
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

The config file (`config/laravel-satis.php`) covers:

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
config(['laravel-satis.tenancy.enabled' => true]);
config(['laravel-satis.tenancy.model' => \App\Models\Team::class]);
config(['laravel-satis.tenancy.foreign_key' => 'team_id']);
config(['laravel-satis.tenancy.resolver' => fn () => auth()->user()?->current_team_id]);
```

### Table Prefix

```php
'table_prefix' => 'satis_',
```

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
    'connection' => null, // null = default connection
    'queue_name' => null, // null = default queue
],
```

### Scheduling

```php
'schedule' => [
    'build' => 'weekly',       // any Laravel Schedule method or null
    'validate' => 'hourly',
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
# Build all packages
php artisan satis:build

# Build for a specific tenant
php artisan satis:build --tenant=1

# Validate credentials
php artisan satis:validate

# Process dependencies
php artisan dependency:packages
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
| `satis:install` | Publish `satis.json` to the project root with interactive setup |
| `satis:build` | Build Satis repository from packages |
| `satis:validate` | Validate package credentials and builds |
| `dependency:packages` | Process and sync package dependencies |

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
