<?php

use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageDownload;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('can create a package', function () {
    $package = Package::factory()->create([
        'name' => 'vendor/my-package',
        'type' => PackageType::Composer,
        'url' => 'https://repo.example.com',
    ]);

    expect($package)->toBeInstanceOf(Package::class)
        ->and($package->name)->toBe('vendor/my-package')
        ->and($package->type)->toBe(PackageType::Composer)
        ->and($package->url)->toBe('https://repo.example.com');
});

it('uses the correct table name with prefix', function () {
    $package = new Package;

    expect($package->getTable())->toBe('satis_packages');
});

it('uses custom table prefix from config', function () {
    config(['satis.table_prefix' => 'custom_']);

    $package = new Package;

    expect($package->getTable())->toBe('custom_packages');
});

it('uses table name without prefix when table_prefix is null', function () {
    config(['satis.table_prefix' => null]);

    $package = new Package;

    expect($package->getTable())->toBe('packages');
});

it('casts type to PackageType enum', function () {
    $package = Package::factory()->create(['type' => 'composer']);

    expect($package->type)->toBe(PackageType::Composer);
});

it('casts is_credentials_validated to boolean', function () {
    $package = Package::factory()->create(['is_credentials_validated' => 1]);

    expect($package->is_credentials_validated)->toBeBool()->toBeTrue();
});

it('hides sensitive fields in array representation', function () {
    $package = Package::factory()->create();

    $array = $package->toArray();

    expect($array)->not->toHaveKey('password');
});

it('has a tokens relationship', function () {
    $package = Package::factory()->create();
    $token = Token::factory()->create();

    $package->tokens()->attach($token->id);

    expect($package->tokens)->toHaveCount(1)
        ->and($package->tokens->first()->id)->toBe($token->id);
});

it('has a packageReleases relationship', function () {
    $package = Package::factory()->create();
    PackageRelease::factory()->create(['package_id' => $package->id]);

    expect($package->packageReleases)->toHaveCount(1);
});

it('has a packageRelease relationship', function () {
    $package = Package::factory()->create();
    PackageRelease::factory()->create(['package_id' => $package->id, 'version' => '1.0.0']);
    PackageRelease::factory()->create(['package_id' => $package->id, 'version' => '2.0.0']);

    expect($package->packageRelease)->toBeInstanceOf(PackageRelease::class)
        ->and($package->packageRelease->version)->toBe('2.0.0');
});

it('has a packageDownloads relationship', function () {
    $package = Package::factory()->create();
    PackageDownload::create([
        'package_id' => $package->id,
        'version' => '1.0.0',
        'downloads' => 10,
    ]);

    expect($package->packageDownloads)->toHaveCount(1);
});

it('auto-generates webhook_secret on creation for github type', function () {
    $package = Package::factory()->github()->create(['webhook_secret' => null]);

    expect($package->webhook_secret)->not->toBeNull()
        ->and(strlen($package->webhook_secret))->toBe(40);
});

it('auto-generates reference on creation', function () {
    $package = Package::factory()->create(['reference' => null]);

    expect($package->reference)->not->toBeNull()
        ->and(strlen($package->reference))->toBe(20);
});

it('can create a github type package', function () {
    $package = Package::factory()->github()->create();

    expect($package->type)->toBe(PackageType::Github);
});

it('can create a validated package', function () {
    $package = Package::factory()->validated()->create();

    expect($package->is_credentials_validated)->toBeTrue()
        ->and($package->credentials_validated_at)->not->toBeNull();
});

it('casts is_dev to boolean', function () {
    $package = Package::factory()->create(['is_dev' => 1]);

    expect($package->is_dev)->toBeBool()->toBeTrue();
});

it('defaults is_dev to false', function () {
    $package = Package::factory()->create();

    expect($package->is_dev)->toBeFalse();
});

it('can create a dev package', function () {
    $package = Package::factory()->dev()->create();

    expect($package->is_dev)->toBeTrue();
});
