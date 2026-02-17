<?php

use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Jobs\AddDependencyDefaultByPackage;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('creates a new package from dependency when it does not exist', function () {
    $package = Package::factory()->create([
        'name' => 'vendor/main-package',
        'type' => PackageType::Composer,
        'url' => 'https://repo.example.com/main-package.git',
        'username' => 'user',
        'password' => 'secret',
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/dependency-package');
    $job->handle();

    $newPackage = Package::where('name', 'vendor/dependency-package')->first();

    expect($newPackage)->not->toBeNull()
        ->and($newPackage->type)->toBe(PackageType::Composer)
        ->and($newPackage->url)->toBe('https://repo.example.com/main-package.git')
        ->and($newPackage->username)->toBe('user')
        ->and($newPackage->password)->toBe('secret');
});

it('does not create duplicate package when it already exists', function () {
    $package = Package::factory()->create([
        'name' => 'vendor/main-package',
        'type' => PackageType::Composer,
        'url' => 'https://repo.example.com/main-package.git',
    ]);

    Package::factory()->create([
        'name' => 'vendor/dependency-package',
        'type' => PackageType::Github,
        'url' => 'https://github.com/vendor/dependency-package.git',
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/dependency-package');
    $job->handle();

    $packages = Package::where('name', 'vendor/dependency-package')->get();

    expect($packages)->toHaveCount(1)
        ->and($packages->first()->type)->toBe(PackageType::Github)
        ->and($packages->first()->url)->toBe('https://github.com/vendor/dependency-package.git');
});

it('copies github type from parent package', function () {
    $package = Package::factory()->github()->create([
        'name' => 'vendor/main-package',
        'url' => 'https://github.com/vendor/main-package.git',
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/github-dep');
    $job->handle();

    $newPackage = Package::where('name', 'vendor/github-dep')->first();

    expect($newPackage)->not->toBeNull()
        ->and($newPackage->type)->toBe(PackageType::Github);
});

it('copies null credentials from parent package', function () {
    $package = Package::factory()->create([
        'name' => 'vendor/main-package',
        'username' => null,
        'password' => null,
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/no-auth-dep');
    $job->handle();

    $newPackage = Package::where('name', 'vendor/no-auth-dep')->first();

    expect($newPackage)->not->toBeNull()
        ->and($newPackage->username)->toBeNull()
        ->and($newPackage->password)->toBeNull();
});
