<?php

use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('can create a credential', function () {
    $credential = Credential::factory()->create([
        'name' => 'My Repo',
        'url' => 'https://repo.example.com',
        'email' => 'user@example.com',
    ]);

    expect($credential)->toBeInstanceOf(Credential::class)
        ->and($credential->name)->toBe('My Repo')
        ->and($credential->url)->toBe('https://repo.example.com')
        ->and($credential->email)->toBe('user@example.com');
});

it('uses the correct table name with prefix', function () {
    $credential = new Credential;

    expect($credential->getTable())->toBe('satis_credentials');
});

it('uses custom table prefix from config', function () {
    config(['satis.table_prefix' => 'custom_']);

    $credential = new Credential;

    expect($credential->getTable())->toBe('custom_credentials');
});

it('uses table name without prefix when table_prefix is null', function () {
    config(['satis.table_prefix' => null]);

    $credential = new Credential;

    expect($credential->getTable())->toBe('credentials');
});

it('casts is_validated to boolean', function () {
    $credential = Credential::factory()->create(['is_validated' => 1]);

    expect($credential->is_validated)->toBeBool()->toBeTrue();
});

it('casts validated_at to datetime', function () {
    $credential = Credential::factory()->validated()->create();

    expect($credential->validated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('hides password in array representation', function () {
    $credential = Credential::factory()->create();

    $array = $credential->toArray();

    expect($array)->not->toHaveKey('password');
});

it('has a packages relationship', function () {
    $credential = Credential::factory()->create();
    Package::factory()->create(['credential_id' => $credential->id]);
    Package::factory()->create(['credential_id' => $credential->id]);

    expect($credential->packages)->toHaveCount(2);
});

it('has a display_name accessor', function () {
    $credential = Credential::factory()->create([
        'name' => 'My Repo',
        'email' => 'user@example.com',
    ]);

    expect($credential->display_name)->toBe('My Repo (user@example.com)');
});

it('can create a validated credential', function () {
    $credential = Credential::factory()->validated()->create();

    expect($credential->is_validated)->toBeTrue()
        ->and($credential->validated_at)->not->toBeNull();
});

it('defaults is_validated to false', function () {
    $credential = Credential::factory()->create();

    expect($credential->is_validated)->toBeFalse()
        ->and($credential->validated_at)->toBeNull();
});
