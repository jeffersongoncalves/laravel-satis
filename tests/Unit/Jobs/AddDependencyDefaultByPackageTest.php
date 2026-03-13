<?php

use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Jobs\AddDependencyDefaultByPackage;
use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('creates a new package from dependency when it does not exist', function () {
    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user',
        'password' => 'secret',
    ]);

    $package = Package::factory()->create([
        'name' => 'vendor/main-package',
        'type' => PackageType::Composer,
        'credential_id' => $credential->id,
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/dependency-package');
    $job->handle();

    $newPackage = Package::where('name', 'vendor/dependency-package')->first();

    expect($newPackage)->not->toBeNull()
        ->and($newPackage->type)->toBe(PackageType::Composer)
        ->and($newPackage->credential_id)->toBe($credential->id);
});

it('does not create duplicate package when it already exists', function () {
    $package = Package::factory()->create([
        'name' => 'vendor/main-package',
        'type' => PackageType::Composer,
    ]);

    Package::factory()->create([
        'name' => 'vendor/dependency-package',
        'type' => PackageType::Github,
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/dependency-package');
    $job->handle();

    $packages = Package::where('name', 'vendor/dependency-package')->get();

    expect($packages)->toHaveCount(1)
        ->and($packages->first()->type)->toBe(PackageType::Github);
});

it('copies github type from parent package', function () {
    $package = Package::factory()->github()->create([
        'name' => 'vendor/main-package',
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/github-dep');
    $job->handle();

    $newPackage = Package::where('name', 'vendor/github-dep')->first();

    expect($newPackage)->not->toBeNull()
        ->and($newPackage->type)->toBe(PackageType::Github);
});

it('copies credential_id from parent package', function () {
    $credential = Credential::factory()->create();

    $package = Package::factory()->create([
        'name' => 'vendor/main-package',
        'credential_id' => $credential->id,
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/dep-with-cred');
    $job->handle();

    $newPackage = Package::where('name', 'vendor/dep-with-cred')->first();

    expect($newPackage)->not->toBeNull()
        ->and($newPackage->credential_id)->toBe($credential->id);
});

it('creates package with null credential_id from parent without credential', function () {
    $package = Package::factory()->withoutCredential()->create([
        'name' => 'vendor/main-package',
    ]);

    $job = new AddDependencyDefaultByPackage($package, 'vendor/no-auth-dep');
    $job->handle();

    $newPackage = Package::where('name', 'vendor/no-auth-dep')->first();

    expect($newPackage)->not->toBeNull()
        ->and($newPackage->credential_id)->toBeNull();
});
