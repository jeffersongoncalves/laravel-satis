<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Packagist;

it('auto-detects public dependency type via packagist API when name contains slash', function () {
    Http::fake([
        'repo.packagist.org/p2/illuminate/support.json' => Http::response(['packages' => []], 200),
    ]);

    $dependency = Dependency::create([
        'name' => 'illuminate/support',
    ]);

    expect($dependency->type)->toBe(DependencyType::Public);

    $packagist = Packagist::where('name', 'illuminate/support')->first();
    expect($packagist)->not->toBeNull()
        ->and($packagist->type)->toBe(DependencyType::Public);
});

it('auto-detects private dependency type via packagist API when package not found', function () {
    Http::fake([
        'repo.packagist.org/p2/vendor/private-package.json' => Http::response([], 404),
    ]);

    $dependency = Dependency::create([
        'name' => 'vendor/private-package',
    ]);

    expect($dependency->type)->toBe(DependencyType::Private);

    $packagist = Packagist::where('name', 'vendor/private-package')->first();
    expect($packagist)->not->toBeNull()
        ->and($packagist->type)->toBe(DependencyType::Private);
});

it('uses cached packagist entry instead of making API call', function () {
    Packagist::create(['name' => 'illuminate/support', 'type' => 'public']);

    Http::fake();

    $dependency = Dependency::create([
        'name' => 'illuminate/support',
    ]);

    expect($dependency->type)->toBe(DependencyType::Public);

    Http::assertNothingSent();
});

it('sets public type for dependency names without slash', function () {
    $dependency = Dependency::create([
        'name' => 'php',
    ]);

    expect($dependency->type)->toBe(DependencyType::Public);
});

it('overrides explicit type with detected type for names with slash', function () {
    Http::fake([
        'repo.packagist.org/p2/vendor/package.json' => Http::response(['packages' => []], 200),
    ]);

    $dependency = Dependency::create([
        'name' => 'vendor/package',
        'type' => 'private',
    ]);

    expect($dependency->type)->toBe(DependencyType::Public);
});

it('clears cache on dependency creation', function () {
    Http::fake([
        'repo.packagist.org/*' => Http::response([], 404),
    ]);

    Cache::put('laravel-satis:dependencies', 'cached-data');
    Cache::put('laravel-satis:dependencies-count', 5);

    Dependency::create(['name' => 'test/package']);

    expect(Cache::get('laravel-satis:dependencies'))->toBeNull()
        ->and(Cache::get('laravel-satis:dependencies-count'))->toBeNull();
});

it('clears cache on dependency deletion', function () {
    Http::fake([
        'repo.packagist.org/*' => Http::response([], 404),
    ]);

    $dependency = Dependency::create(['name' => 'test/package']);

    Cache::put('laravel-satis:dependencies', 'cached-data');
    $dependency->delete();

    expect(Cache::get('laravel-satis:dependencies'))->toBeNull();
});
