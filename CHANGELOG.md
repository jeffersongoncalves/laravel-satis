# Changelog

All notable changes to this project will be documented in this file.

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
