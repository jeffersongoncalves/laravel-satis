<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | When enabled, all data (packages, tokens) is isolated per tenant.
    | The tenant model must implement the required relationship or have
    | the foreign key configured below.
    |
    | The 'resolver' should be a callable that returns the current tenant ID.
    | Example: fn () => auth()->user()->team_id
    |
    */
    'tenancy' => [
        'enabled' => false,
        'model' => null,
        'foreign_key' => null,
        'ownership_relationship' => null,
        'resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix applied to all tables created by the package to avoid
    | collision with existing application tables.
    | Set to null to use table names without a prefix.
    |
    */
    'table_prefix' => 'satis_',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Models used by the package. Can be overridden to extend the default
    | behavior. Custom models must extend the originals.
    |
    */
    'models' => [
        'package' => \JeffersonGoncalves\LaravelSatis\Models\Package::class,
        'token' => \JeffersonGoncalves\LaravelSatis\Models\Token::class,
        'dependency' => \JeffersonGoncalves\LaravelSatis\Models\Dependency::class,
        'package_release' => \JeffersonGoncalves\LaravelSatis\Models\PackageRelease::class,
        'package_download' => \JeffersonGoncalves\LaravelSatis\Models\PackageDownload::class,
        'packagist' => \JeffersonGoncalves\LaravelSatis\Models\Packagist::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Disk and path where Satis builds are stored.
    | Compatible with any Laravel filesystem driver.
    |
    */
    'storage_disk' => 'local',
    'storage_path' => 'satis',

    /*
    |--------------------------------------------------------------------------
    | Satis Binary
    |--------------------------------------------------------------------------
    |
    | Path to the Satis binary. When null, uses the binary included
    | as a package dependency (vendor/bin/satis).
    |
    */
    'satis_binary' => null,

    /*
    |--------------------------------------------------------------------------
    | Satis Base Config
    |--------------------------------------------------------------------------
    |
    | Base satis.json configuration generated on each build.
    | Merged with repositories and requires from each build.
    |
    */
    'satis' => [
        'name' => 'my/repository',
        'output_html' => false,
        'archive' => [
            'directory' => 'archives',
            'skip_dev' => true,
        ],
        'minimum_stability' => 'stable',
        'secure_http' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Queue configuration for build and processing jobs.
    | When null, uses the application defaults.
    |
    */
    'queue' => [
        'connection' => null,
        'queue_name' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    |
    | Frequency of scheduled commands. Accepts Laravel Schedule methods
    | (weekly, hourly, daily, etc.) or null to disable.
    |
    */
    'schedule' => [
        'build' => 'weekly',
        'validate' => 'hourly',
        'dependencies' => 'weekly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    |
    | Guard and provider for Composer token authentication.
    | Automatically registered by the package in auth config.
    |
    */
    'auth' => [
        'guard' => 'satis-token',
        'provider' => 'satis-tokens',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Prefixes and middleware for package routes.
    | Set composer_prefix to null to serve routes without a prefix.
    |
    */
    'routes' => [
        'api_prefix' => 'api/satis',
        'composer_prefix' => 'satis',
        'middleware' => ['api'],
    ],
];
