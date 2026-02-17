# Changelog

All notable changes to this project will be documented in this file.

## 1.13.0 - 2026-02-17

### What's Changed

#### New Features

- **Contract/Interface pattern for all models** — Added 8 contract interfaces (`PackageContract`, `TokenContract`, `DependencyContract`, `PackageReleaseContract`, `PackageDownloadContract`, `DependencyPackageReleaseContract`, `PackageTokenContract`, `PackagistContract`) ensuring custom models implement required methods
- **ModelResolver validation with cache** — `ModelResolver` now validates that configured models implement their corresponding contract and caches resolved classes for performance
- **Container bindings** — All contracts are bound in the service container, enabling dependency injection via interfaces

#### Config Changes

- Added `package_token` to the `models` config array
- Updated models config comment to reference contracts

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-satis/compare/1.12.0...1.13.0

## 1.12.0 - 2026-02-17

### What's Changed

- **feat:** Dispatch `ValidatePackageCredentialsJob` automatically on package creation to validate credentials via queue

## 1.11.1 - 2026-02-17

### What's Changed

- **refactor:** Remove unused `PackageDownloadObserver` and `PackageReleaseObserver` (empty methods)
- **refactor:** Remove cache clearing logic from all observers (`PackageObserver`, `DependencyObserver`, `TokenObserver`)
- **fix:** Generate `webhook_secret` only for Github type packages (Composer packages get null)
- **fix:** Remove `username` and `webhook_secret` from Package `$hidden` (keep only `password`)

## 1.10.1 - 2026-02-17

### What's Changed

- **fix:** Default release time to current ISO 8601 date (`2024-02-12T14:56:55+00:00`) when `time` is null in package version data

## 1.11.0 - 2026-02-17

### Feature

- Add `datetime` cast to `PackageRelease::time` field (converts ISO 8601 string to Carbon instance)

## 1.10.0 - 2026-02-17

### What's Changed

- **fix:** Align model relationships with app naming conventions
  - Rename `releases()` to `packageReleases()` and `downloads()` to `packageDownloads()` on Package model
  - Add `packageRelease()` HasOne relationship (latest version) to Package
  - Add `packageReleaseRequires()` HasMany to Dependency and PackageRelease
  - Add `dependencyPackageRelease` to ModelResolver and config for extensibility
  

## 1.9.2 - 2026-02-17

### What's Changed

- **feat:** Add \ static factory method that accepts \ for flexible enum instantiation
- Add tests for \ covering string values, self instances, and invalid values

## 1.9.1 - 2026-02-17

### PHPDoc & PHPStan Improvements

#### PHPDoc

- Added `@property` and `@property-read` annotations to all 8 models: Package, Token, Dependency, DependencyPackageRelease, PackageDownload, PackageToken, Packagist, PackageRelease
- Documented virtual attributes (`folder`, `name_provider`, `composer_command`, `webhook_url`, `composer_repository`)
- Documented all relationship properties with typed collections

#### PHPStan

- Fixed `EloquentTokenProvider` type safety (`retrieveByToken`, `updateRememberToken`, `retrieveByCredentials`)
- Regenerated baseline: reduced from 25 to 10 ignored errors

## 1.9.0 - 2026-02-17

### EloquentTokenProvider Refactored

#### Breaking Changes

- Removed `Hasher` dependency from `EloquentTokenProvider` constructor (now receives only the model class)
- `validateCredentials()` now always returns `true` (authentication is handled by credential lookup)
- `retrieveById()` uses `getAuthIdentifierName()` (model `id`) instead of `getAuthPasswordName()`

#### New Features

- Added `withQuery()` / `getQueryCallback()` for query customization before user retrieval
- Added `getModel()` / `setModel()` accessors
- Added `createModel()` and `newModelQuery()` following Laravel's standard Eloquent provider pattern
- `retrieveByCredentials()` now supports array values (`whereIn`), closures, and `Arrayable` instances

#### Fixes

- Updated `LaravelSatisServiceProvider` to match new constructor signature

## 1.8.2 - 2026-02-17

### Token Auth Simplification

- Removed `getAuthIdentifierName()` and `getAuthIdentifier()` overrides from Token model (uses default `id` from Authenticatable trait)
- Kept only `getAuthPasswordName()` override returning `'token'`
- Updated `EloquentTokenProvider` to use `getAuthPasswordName()` for token lookup and `getAuthPassword()` for credential validation
- Updated TokenTest to verify auth password instead of auth identifier

## 1.8.1 - 2026-02-17

### Token Model Enhancements

- Added `email` default value `'token'` in migration and model `$attributes`
- Added virtual attribute `composer_command` — generates `composer global config http-basic.{host} token {token}`
- Added virtual attribute `composer_repository` — generates `composer config repositories.{name} composer {url}`
- Updated TokenFactory to use `'token'` as default email

## 1.8.0 - 2026-02-17

### Refactored Models & Traits

#### GenerateCode Trait

- Rewritten to use generic `generateCode(string $column)` method with database uniqueness check
- Added abstract methods `getColumnCode()` and `getLengthCode()` that models must implement
- Added `getLengthCodeByColumn()` helper with fallback to 8 characters
- Removed old methods: `generateUniqueCode()`, `generateToken()`, `generateWebhookSecret()`, `generateReference()`

#### Package Model

- Implemented `getColumnCode()` and `getLengthCode()` for `webhook_secret` (40) and `reference` (20)
- Added virtual attributes: `folder`, `name_provider`, `composer_command`, `webhook_url`
- Named webhook route `webhooks.github` for URL generation

#### Token Model

- Implemented `getColumnCode()` and `getLengthCode()` for `token` (64)
- Now uses `Illuminate\Auth\Authenticatable` trait with `AuthenticatableContract` interface
- Overrides `getAuthIdentifierName()`, `getAuthIdentifier()`, and `getAuthPasswordName()` for token-based auth

#### PackageRelease Model

- Added virtual attribute `name` (combines package name and version)

#### Tests

- Rewrote GenerateCodeTest for new API (10 tests covering columns, lengths, generation, uniqueness)
- Updated TokenFactory to use `generateCode('token')`
- Total: 192 tests, 346 assertions

## 1.7.0 - 2026-02-17

### Intelligent Validation with Timestamp Comparison

#### ValidateTenantSatisBuild

- Compares `packages.json` modification time with `Package::max('updated_at')`
- Only triggers rebuild (`SyncTenantPackages`) when packages have been updated since the last build
- Skips unnecessary rebuilds when builds are already up to date
- Falls back to rebuild if `packages.json` does not exist

#### ValidateTokenSatisBuild

- Compares `packages.json` modification time with pivot table `updated_at` timestamp
- Only triggers rebuild (`SyncTokenPackages`) when token-package associations changed since last build
- Checks tenant build exists before attempting token validation
- Falls back to rebuild if token build is missing or has empty/invalid content

#### auth.json Support

- New `CreateAuthJson` action generates `auth.json` with `http-basic` credentials for Composer-type packages
- `SyncTenantPackages` now sets `COMPOSER_HOME` environment variable pointing to the auth.json directory
- Enables authenticated access to private Composer repositories during Satis builds
- Automatically filters out GitHub packages (they use different auth mechanism)

#### Tests

- 13 new tests covering all validation scenarios and auth.json generation
- Total: 189 tests, 343 assertions

## 1.6.0 - 2026-02-17

### New Artisan Commands

#### satis:clean

- Removes all Satis repository builds from storage
- Includes confirmation prompt to prevent accidental deletion
- Use `--force` flag to skip confirmation (useful for CI/CD)

#### satis:sanitize

- Removes `transport-options` (credentials) from all Satis JSON files
- Iterates through all directories in the Satis storage path
- Uses the existing `SanitizeSatisPackages` action for consistency
- Scheduled daily by default

#### satis:token-build

- Builds Satis repository for token-specific packages
- Use `--token=ID` to build for a specific token
- Without options, builds for all tokens that have packages
- Dispatches `SyncTokenPackages` job for each token
- Scheduled weekly by default

#### Other Changes

- Updated `config/satis.php` with new schedule entries (`token_build`, `sanitize`)
- Registered all new commands in ServiceProvider with scheduling support
- Pint code style fixes (line endings)

#### Available Commands Summary

| Command | Description |
|---------|-------------|
| `satis:build` | Build Satis repository (tenant-based) |
| `satis:token-build` | Build Satis repository (token-based) |
| `satis:validate` | Validate Satis builds and credentials |
| `satis:clean` | Clean all Satis builds from storage |
| `satis:sanitize` | Remove credentials from Satis JSON files |
| `dependency:packages` | Process package dependencies |

#### Tests

- 11 new tests for the 3 commands
- Total: 176 tests, 324 assertions

## 1.5.1 - 2026-02-17

### What's Changed

#### New Features

- **`is_dev` field on Package**: Boolean field to differentiate `composer require` from `composer require --dev` packages
- **Configurable queue timeout**: New `config('satis.queue.timeout')` option (default: 86400s / 24h) applied to all jobs
- **Sanitize transport-options**: `SanitizeSatisPackages::sanitizeDirectory()` removes credential-containing `transport-options` from Satis JSON output files (p2/ and include/ directories)

#### Improvements

- **Post-build sanitization**: `SyncTenantPackages` and `SyncTokenPackages` automatically sanitize JSON files after successful builds
- **Post-build dependency processing**: `SyncTenantPackages` dispatches `ProcessPackageDependency` after successful builds
- **Factory state**: New `dev()` factory state for creating dev packages

#### Internal

- Updated PHPStan baseline
- Applied Pint code formatting
- Added tests for is_dev, sanitizeDirectory, and transport-options removal (158 tests, 265 assertions)

## 1.5.0 - 2026-02-17

### Added

- `AddDependencyDefaultByPackage` job — auto-creates a Package for private dependencies, copying type/url/credentials from the parent package
- `ProcessPackageDependency` action now dispatches `AddDependencyDefaultByPackage` when a dependency is detected as private

### Changed

- `ProcessPackageDependency` action: removed `resolveType()` method (type detection now delegated to `DependencyObserver` via `Packagist::getDependencyType()`)
- `ProcessPackageDependency` action: `firstOrCreate` now passes initial `versions` array, uses `collect()` for unique version management, and handles array version values with `implode(',')`
- `ProcessPackageDependency` action: `execute()` now accepts optional `$package` parameter for dispatching dependency jobs

## 1.4.4 - 2026-02-17

### Added

- `Packagist::getDependencyType()` — resolves dependency type by checking local cache first, then querying Packagist API (`repo.packagist.org/p2`)
- `Packagist::getRepoByPackagist()` — HTTP lookup against Packagist API with `ConnectionException` fallback to private

### Changed

- `DependencyObserver::creating()` — now always sets the type using `Packagist::getDependencyType()` for names containing `/`, and `DependencyType::Public` for names without `/` (e.g. `php`, `ext-mbstring`)

## 1.4.3 - 2026-02-17

### Fixed

- Fixed migration stubs using old config key `laravel-satis.*` instead of `satis.*`
- Fixed null `table_prefix` handling in models and migration stubs — replaced `config('satis.table_prefix', 'satis_')` with `(config('satis.table_prefix') ?? '')` to properly support null values

## 1.4.2 - 2026-02-17

### Changed

- Allow `table_prefix` config to be `null` for unprefixed table names
- Added null `table_prefix` tests for all models (Package, Token, Dependency, PackageRelease, PackageDownload, Packagist)

## 1.4.1 - 2026-02-17

### Added

- Support for `null` `composer_prefix` — serves composer routes without a URL prefix (e.g. `/packages.json` instead of `/satis/packages.json`)
- Handles tenancy prefix correctly when `composer_prefix` is `null`

## 1.4.0 - 2026-02-17

### Removed

- Removed `satis:install` command — the `satis.json` is now generated at runtime from `config/satis.php` values
- Removed `stubs/satis.json.stub` — no longer needed

## 1.3.5 - 2026-02-17

### Changed

- Renamed config file from `config/laravel-satis.php` to `config/satis.php`
- Updated all config references from `config('laravel-satis.*')` to `config('satis.*')`

## 1.3.4 - 2026-02-17

### Fixed

- Updated Boost guidelines with missing config options (`satis_binary`, `auth`, `tenancy.ownership_relationship`, `secure_http`)
- Added `satis:install` command to Boost artisan commands reference
- Added Installation section to SKILL.md with `satis:install` usage
- Removed `setHomepage` from SatisConfig example (auto-injected during builds)

## 1.3.3 - 2026-02-17

### Changed

- Removed `homepage` option from `satis:install` command (automatically injected during builds)

## 1.3.2 - 2026-02-17

### Fixed

- Fixed pt_BR translation for public dependency type (`Publica` → `Pública`)

## 1.3.1 - 2026-02-17

### Fixed

- Corrected `vendor:publish` tag names to `satis-config` and `satis-migrations`

## 1.3.0 - 2026-02-17

### Added

- `satis:install` command to publish `satis.json` to the project root with interactive setup
  
  - Prompts for repository name
  - Supports `--name` and `--force` options
  - Confirms before overwriting an existing `satis.json`
  
- PHPStan/Larastan static analysis configuration (`phpstan.neon.dist` + baseline)
  
