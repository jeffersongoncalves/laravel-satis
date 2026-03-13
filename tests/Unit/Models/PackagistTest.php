<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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

it('returns cached dependency type when packagist entry exists', function () {
    Packagist::create(['name' => 'illuminate/support', 'type' => 'public']);

    Http::fake();

    $type = Packagist::getDependencyType('illuminate/support');

    expect($type)->toBe(DependencyType::Public);

    Http::assertNothingSent();
});

it('fetches dependency type from packagist API when entry does not exist', function () {
    Http::fake([
        'repo.packagist.org/p2/illuminate/support.json' => Http::response(['packages' => []], 200),
    ]);

    $type = Packagist::getDependencyType('illuminate/support');

    expect($type)->toBe(DependencyType::Public);

    $packagist = Packagist::where('name', 'illuminate/support')->first();
    expect($packagist)->not->toBeNull()
        ->and($packagist->type)->toBe(DependencyType::Public);
});

it('returns private type when packagist API returns not found', function () {
    Http::fake([
        'repo.packagist.org/p2/vendor/private-pkg.json' => Http::response([], 404),
    ]);

    $type = Packagist::getDependencyType('vendor/private-pkg');

    expect($type)->toBe(DependencyType::Private);

    $packagist = Packagist::where('name', 'vendor/private-pkg')->first();
    expect($packagist)->not->toBeNull()
        ->and($packagist->type)->toBe(DependencyType::Private);
});

it('returns private type when packagist API throws connection exception', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $type = Packagist::getDependencyType('vendor/unreachable-pkg');

    expect($type)->toBe(DependencyType::Private);

    $packagist = Packagist::where('name', 'vendor/unreachable-pkg')->first();
    expect($packagist)->not->toBeNull()
        ->and($packagist->type)->toBe(DependencyType::Private);
});
