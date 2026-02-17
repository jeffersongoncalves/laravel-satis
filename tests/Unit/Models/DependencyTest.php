<?php

use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;

it('can create a dependency', function () {
    $dependency = Dependency::create([
        'name' => 'illuminate/support',
        'versions' => ['^10.0'],
        'type' => 'public',
    ]);

    expect($dependency)->toBeInstanceOf(Dependency::class)
        ->and($dependency->name)->toBe('illuminate/support')
        ->and($dependency->versions)->toBe(['^10.0'])
        ->and($dependency->type)->toBe(DependencyType::Public);
});

it('uses the correct table name with prefix', function () {
    $dependency = new Dependency;

    expect($dependency->getTable())->toBe('satis_dependencies');
});

it('uses table name without prefix when table_prefix is null', function () {
    config(['satis.table_prefix' => null]);

    $dependency = new Dependency;

    expect($dependency->getTable())->toBe('dependencies');
});

it('casts versions to array', function () {
    $dependency = Dependency::create([
        'name' => 'test/package',
        'versions' => ['^1.0', '^2.0'],
        'type' => 'private',
    ]);

    expect($dependency->versions)->toBeArray()
        ->and($dependency->versions)->toBe(['^1.0', '^2.0']);
});

it('casts type to DependencyType enum', function () {
    $dependency = Dependency::create([
        'name' => 'test/package',
        'type' => 'private',
    ]);

    expect($dependency->type)->toBe(DependencyType::Private);
});

it('has a packageReleases relationship', function () {
    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);
    $dependency = Dependency::create([
        'name' => 'illuminate/support',
        'type' => 'public',
    ]);

    $dependency->packageReleases()->attach($release->id, [
        'version' => '^10.0',
        'package_id' => $package->id,
    ]);

    expect($dependency->packageReleases)->toHaveCount(1);
});
