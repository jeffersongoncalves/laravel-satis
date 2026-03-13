<?php

use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\DependencyPackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageDownload;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\PackageToken;
use JeffersonGoncalves\LaravelSatis\Models\Packagist;
use JeffersonGoncalves\LaravelSatis\Models\Token;

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
    | behavior. Custom models must implement the corresponding contract
    | interface (see src/Models/Contracts/).
    |
    */
    'models' => [
        'credential' => Credential::class,
        'package' => Package::class,
        'token' => Token::class,
        'dependency' => Dependency::class,
        'package_release' => PackageRelease::class,
        'package_download' => PackageDownload::class,
        'dependency_package_release' => DependencyPackageRelease::class,
        'package_token' => PackageToken::class,
        'packagist' => Packagist::class,
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
        'output-html' => false,
        'archive' => [
            'directory' => 'archives',
            'skip-dev' => true,
        ],
        'minimum-stability' => 'stable',
        'config' => [
            'secure-http' => false,
        ],
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
        'timeout' => 86400,
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
        'token_build' => 'weekly',
        'validate' => 'hourly',
        'sanitize' => 'daily',
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
        'guard' => 'token',
        'provider' => 'tokens',
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
