<?php

use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Packagist;

it('can create a packagist entry', function () {
    $packagist = Packagist::create([
        'name' => 'illuminate/support',
        'type' => 'public',
    ]);

    expect($packagist)->toBeInstanceOf(Packagist::class)
        ->and($packagist->name)->toBe('illuminate/support')
        ->and($packagist->type)->toBe(DependencyType::Public);
});

it('uses the correct table name with prefix', function () {
    $packagist = new Packagist;

    expect($packagist->getTable())->toBe('satis_packagists');
});

it('uses table name without prefix when table_prefix is null', function () {
    config(['satis.table_prefix' => null]);

    $packagist = new Packagist;

    expect($packagist->getTable())->toBe('packagists');
});

it('casts type to DependencyType enum', function () {
    $packagist = Packagist::create([
        'name' => 'test/package',
        'type' => 'private',
    ]);

    expect($packagist->type)->toBe(DependencyType::Private);
});
