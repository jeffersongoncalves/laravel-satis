<?php

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Packagist;

it('auto-detects public dependency type when packagist entry exists', function () {
    Packagist::create(['name' => 'illuminate/support', 'type' => 'public']);

    $dependency = Dependency::create([
        'name' => 'illuminate/support',
    ]);

    expect($dependency->type)->toBe(DependencyType::Public);
});

it('auto-detects private dependency type when packagist entry does not exist', function () {
    $dependency = Dependency::create([
        'name' => 'vendor/private-package',
    ]);

    expect($dependency->type)->toBe(DependencyType::Private);
});

it('preserves explicit type when provided', function () {
    $dependency = Dependency::create([
        'name' => 'vendor/package',
        'type' => 'public',
    ]);

    expect($dependency->type)->toBe(DependencyType::Public);
});

it('clears cache on dependency creation', function () {
    Cache::put('laravel-satis:dependencies', 'cached-data');
    Cache::put('laravel-satis:dependencies-count', 5);

    Dependency::create(['name' => 'test/package']);

    expect(Cache::get('laravel-satis:dependencies'))->toBeNull()
        ->and(Cache::get('laravel-satis:dependencies-count'))->toBeNull();
});

it('clears cache on dependency deletion', function () {
    $dependency = Dependency::create(['name' => 'test/package']);

    Cache::put('laravel-satis:dependencies', 'cached-data');
    $dependency->delete();

    expect(Cache::get('laravel-satis:dependencies'))->toBeNull();
});
