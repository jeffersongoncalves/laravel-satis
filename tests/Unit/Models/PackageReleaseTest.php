<?php

use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;

it('can create a package release', function () {
    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create([
        'package_id' => $package->id,
        'version' => '1.0.0',
    ]);

    expect($release)->toBeInstanceOf(PackageRelease::class)
        ->and($release->version)->toBe('1.0.0')
        ->and($release->package_id)->toBe($package->id);
});

it('uses the correct table name with prefix', function () {
    $release = new PackageRelease;

    expect($release->getTable())->toBe('satis_package_releases');
});

it('belongs to a package', function () {
    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);

    expect($release->package)->toBeInstanceOf(Package::class)
        ->and($release->package->id)->toBe($package->id);
});

it('has a dependencies relationship', function () {
    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);
    $dependency = Dependency::create([
        'name' => 'illuminate/support',
        'versions' => ['8.*'],
        'type' => 'public',
    ]);

    $release->dependencies()->attach($dependency->id, [
        'version' => '^8.0',
        'package_id' => $package->id,
    ]);

    expect($release->dependencies)->toHaveCount(1)
        ->and($release->dependencies->first()->name)->toBe('illuminate/support');
});
