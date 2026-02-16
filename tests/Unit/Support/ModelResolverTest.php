<?php

use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageDownload;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\Packagist;
use JeffersonGoncalves\LaravelSatis\Models\Token;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

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

it('resolves the packagist model', function () {
    expect(ModelResolver::packagist())->toBe(Packagist::class);
});

it('resolves custom models from config', function () {
    config(['laravel-satis.models.package' => 'App\\Models\\CustomPackage']);

    expect(ModelResolver::package())->toBe('App\\Models\\CustomPackage');
});
