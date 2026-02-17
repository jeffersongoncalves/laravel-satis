# Changelog

All notable changes to this project will be documented in this file.

## 1.3.1 - 2026-02-17

### Fixed

- Corrected `vendor:publish` tag names to `satis-config` and `satis-migrations`

## 1.3.0 - 2026-02-17

### Added

- `satis:install` command to publish `satis.json` to the project root with interactive setup
  - Prompts for repository name and homepage URL
  - Supports `--name`, `--homepage`, and `--force` options
  - Confirms before overwriting an existing `satis.json`
- PHPStan/Larastan static analysis configuration (`phpstan.neon.dist` + baseline)
