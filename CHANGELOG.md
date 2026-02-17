# Changelog

All notable changes to this project will be documented in this file.

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
  
