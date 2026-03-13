## Laravel Satis

This package provides private Composer repository management using Satis with token-based authentication, credential management, multi-tenancy support, and dependency tracking.

### Key Concepts

- **Credentials**: Centralized authentication (URL, email, password) for accessing private repositories.
- **Packages**: Composer or GitHub VCS repositories linked to credentials for private hosting.
- **Tokens**: Authentication credentials (64-char strings) granting access to specific packages.
- **Releases**: Package versions parsed from Satis builds with dependency tracking.
- **Multi-Tenancy**: Optional tenant isolation for credentials, packages, tokens, and builds.

### Models & Relationships

- `Credential` hasMany `Package` (stores url, email, password)
- `Package` belongsTo `Credential`, hasMany `PackageRelease`, hasMany `PackageDownload`, belongsToMany `Token`
- `Token` belongsToMany `Package` (implements `Authenticatable`)
- `PackageRelease` belongsTo `Package`, belongsToMany `Dependency`
- `Dependency` belongsToMany `PackageRelease`
- `Packagist` stores public package lookup data

All models use `ModelResolver` for class resolution — always use `ModelResolver::credential()`, `ModelResolver::package()` etc. instead of hardcoding model classes.

### Configuration

Config file: `config/satis.php`

Key options:
- `tenancy.enabled` — Enable multi-tenancy with custom resolver
- `tenancy.ownership_relationship` — Relationship name for tenant ownership
- `table_prefix` — Database table prefix (default: `satis_`)
- `storage_disk` / `storage_path` — Where Satis builds are stored
- `satis_binary` — Custom path to the Satis binary (default: `vendor/bin/satis`)
- `satis` — Base Satis configuration (name, archive settings, stability, secure_http)
- `auth.guard` / `auth.provider` — Guard and provider for token authentication
- `routes.api_prefix` / `routes.composer_prefix` — Route prefixes
- `models.*` — Override default model classes (credential, package, token, etc.)

### Artisan Commands

- `satis:build` — Build Satis repository (dispatches `SyncTenantPackages` job)
- `satis:token-build` — Build Satis repository per token
- `satis:validate` — Validate builds and package credentials
- `satis:sanitize` — Remove credentials from Satis JSON files
- `satis:clean` — Clean all Satis builds from storage
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

### Build Process

Builds are grouped by credential for separate Satis runs:
1. Packages grouped by `credential_id`
2. Each group gets a separate `satis.json` with inline auth URLs (RFC 3986)
3. Retry with exponential backoff on rate-limiting (HTTP 429)
4. Snapshots merged via `MergeSatisPackagesJson` action
5. Output sanitized to remove inline credentials

### Conventions

- Use `ModelResolver` to reference models (allows custom overrides).
- Jobs use `ShouldQueue` with explicit timeout and tries.
- Observers handle cache invalidation and auto-generation of tokens/secrets.
- The `HasTenancy` trait auto-scopes queries and sets tenant FK on creation.
- The `GenerateCode` trait provides static helpers for generating tokens, secrets, and references.
- Packages require a `credential_id` FK — credentials are never stored directly on packages.
