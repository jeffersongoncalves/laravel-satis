## Laravel Satis

This package provides private Composer repository management using Satis with token-based authentication, multi-tenancy support, and dependency tracking.

### Key Concepts

- **Packages**: Composer or GitHub VCS repositories registered for private hosting.
- **Tokens**: Authentication credentials (64-char strings) granting access to specific packages.
- **Releases**: Package versions parsed from Satis builds with dependency tracking.
- **Multi-Tenancy**: Optional tenant isolation for packages, tokens, and builds.

### Models & Relationships

- `Package` hasMany `PackageRelease`, hasMany `PackageDownload`, belongsToMany `Token`
- `Token` belongsToMany `Package` (implements `Authenticatable`)
- `PackageRelease` belongsTo `Package`, belongsToMany `Dependency`
- `Dependency` belongsToMany `PackageRelease`
- `Packagist` stores public package lookup data

All models use `ModelResolver` for class resolution — always use `ModelResolver::package()` instead of hardcoding model classes.

### Configuration

Config file: `config/laravel-satis.php`

Key options:
- `tenancy.enabled` — Enable multi-tenancy with custom resolver
- `tenancy.ownership_relationship` — Relationship name for tenant ownership
- `table_prefix` — Database table prefix (default: `satis_`)
- `storage_disk` / `storage_path` — Where Satis builds are stored
- `satis_binary` — Custom path to the Satis binary (default: `vendor/bin/satis`)
- `satis` — Base Satis configuration (name, archive settings, stability, secure_http)
- `auth.guard` / `auth.provider` — Guard and provider for token authentication
- `routes.api_prefix` / `routes.composer_prefix` — Route prefixes
- `models.*` — Override default model classes

### Artisan Commands

- `satis:install` — Publish `satis.json` to the project root with interactive setup
- `satis:build` — Build Satis repository (dispatches `SyncTenantPackages` job)
- `satis:validate` — Validate builds and package credentials
- `dependency:packages` — Process and sync package dependencies

### Authentication

Uses a custom auth guard (`satis-token`) with `EloquentTokenProvider`. The `EnsureUserHasLicense` middleware authenticates via HTTP Basic Auth password or Bearer token.

### Enums

- `PackageType`: `Composer`, `Github`
- `DependencyType`: `Public`, `Private`

### Routes

Composer routes (prefix: `satis`):
- `GET packages.json` — Root repository metadata
- `GET p2/{vendor}/{package}.json` — Package metadata (v2 API)
- `GET archives/{vendor}/{package}/{file}` — Package archive download
- `GET include/{include}.json` — Include files

API routes (prefix: `api/satis`):
- `POST composer/downloads` — Record download statistics
- `POST webhooks/github/{package:reference}` — GitHub webhook handler

### Conventions

- Use `ModelResolver` to reference models (allows custom overrides).
- Jobs use `ShouldQueue` with explicit timeout and tries.
- Observers handle cache invalidation and auto-generation of tokens/secrets.
- The `HasTenancy` trait auto-scopes queries and sets tenant FK on creation.
- The `GenerateCode` trait provides static helpers for generating tokens, secrets, and references.
