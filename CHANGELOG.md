# Changelog

All notable changes to this project will be documented in this file.

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
