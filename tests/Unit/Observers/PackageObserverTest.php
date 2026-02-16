<?php

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('generates webhook_secret on creation when empty', function () {
    $package = Package::factory()->create(['webhook_secret' => null]);

    expect($package->webhook_secret)->not->toBeNull()
        ->and(strlen($package->webhook_secret))->toBe(40);
});

it('generates reference on creation when empty', function () {
    $package = Package::factory()->create(['reference' => null]);

    expect($package->reference)->not->toBeNull()
        ->and(strlen($package->reference))->toBe(20);
});

it('preserves existing webhook_secret on creation', function () {
    $package = Package::factory()->create(['webhook_secret' => 'my-custom-secret']);

    expect($package->webhook_secret)->toBe('my-custom-secret');
});

it('preserves existing reference on creation', function () {
    $package = Package::factory()->create(['reference' => 'my-custom-ref']);

    expect($package->reference)->toBe('my-custom-ref');
});

it('clears credentials validation when url changes', function () {
    $package = Package::factory()->validated()->create();

    expect($package->is_credentials_validated)->toBeTrue();

    $package->update(['url' => 'https://new-repo.example.com']);
    $package->refresh();

    expect($package->is_credentials_validated)->toBeFalse()
        ->and($package->credentials_validated_at)->toBeNull();
});

it('clears credentials validation when username changes', function () {
    $package = Package::factory()->validated()->create();
    $package->update(['username' => 'new-user']);
    $package->refresh();

    expect($package->is_credentials_validated)->toBeFalse();
});

it('clears credentials validation when password changes', function () {
    $package = Package::factory()->validated()->create();
    $package->update(['password' => 'new-password']);
    $package->refresh();

    expect($package->is_credentials_validated)->toBeFalse();
});

it('clears cache on package creation', function () {
    Cache::put('laravel-satis:packages', 'cached-data');
    Cache::put('laravel-satis:packages-count', 5);

    Package::factory()->create();

    expect(Cache::get('laravel-satis:packages'))->toBeNull()
        ->and(Cache::get('laravel-satis:packages-count'))->toBeNull();
});

it('clears cache on package update', function () {
    $package = Package::factory()->create();

    Cache::put('laravel-satis:packages', 'cached-data');
    $package->update(['name' => 'vendor/new-name']);

    expect(Cache::get('laravel-satis:packages'))->toBeNull();
});

it('clears cache on package deletion', function () {
    $package = Package::factory()->create();

    Cache::put('laravel-satis:packages', 'cached-data');
    $package->delete();

    expect(Cache::get('laravel-satis:packages'))->toBeNull();
});
