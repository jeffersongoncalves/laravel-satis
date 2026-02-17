# Changelog

All notable changes to this project will be documented in this file.

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
  
