<?php

use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\DependencyPackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageDownload;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\PackageToken;
use JeffersonGoncalves\LaravelSatis\Models\Packagist;
use JeffersonGoncalves\LaravelSatis\Models\Token;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

beforeEach(function () {
    ModelResolver::flushCache();
});

it('resolves the credential model', function () {
    expect(ModelResolver::credential())->toBe(Credential::class);
});

it('resolves the package model', function () {
    expect(ModelResolver::package())->toBe(Package::class);
});

it('resolves the token model', function () {
    expect(ModelResolver::token())->toBe(Token::class);
});

it('resolves the dependency model', function () {
    expect(ModelResolver::dependency())->toBe(Dependency::class);
});

it('resolves the package release model', function () {
    expect(ModelResolver::packageRelease())->toBe(PackageRelease::class);
});

it('resolves the package download model', function () {
    expect(ModelResolver::packageDownload())->toBe(PackageDownload::class);
});

it('resolves the dependency package release model', function () {
    expect(ModelResolver::dependencyPackageRelease())->toBe(DependencyPackageRelease::class);
});

it('resolves the package token model', function () {
    expect(ModelResolver::packageToken())->toBe(PackageToken::class);
});

it('resolves the packagist model', function () {
    expect(ModelResolver::packagist())->toBe(Packagist::class);
});

it('throws exception for non-existent model class', function () {
    config(['satis.models.package' => 'App\\Models\\NonExistentModel']);

    ModelResolver::package();
})->throws(InvalidArgumentException::class, 'does not exist');

it('throws exception when model does not implement contract', function () {
    // stdClass exists but does not implement PackageContract
    config(['satis.models.package' => stdClass::class]);

    ModelResolver::package();
})->throws(InvalidArgumentException::class, 'must implement');

it('caches resolved models', function () {
    // First call resolves and caches
    $first = ModelResolver::package();
    // Second call returns from cache
    $second = ModelResolver::package();

    expect($first)->toBe(Package::class)
        ->and($second)->toBe(Package::class);
});

it('flushes cache correctly', function () {
    // Resolve to populate cache
    ModelResolver::package();

    // Flush cache
    ModelResolver::flushCache();

    // Change config — should pick up the new value (which will fail validation)
    config(['satis.models.package' => stdClass::class]);

    expect(fn () => ModelResolver::package())
        ->toThrow(InvalidArgumentException::class);
});
