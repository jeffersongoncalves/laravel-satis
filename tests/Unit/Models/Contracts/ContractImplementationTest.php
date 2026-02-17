<?php

use JeffersonGoncalves\LaravelSatis\Models\Contracts\DependencyContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\DependencyPackageReleaseContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageDownloadContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageReleaseContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageTokenContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackagistContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\TokenContract;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\DependencyPackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageDownload;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;
use JeffersonGoncalves\LaravelSatis\Models\PackageToken;
use JeffersonGoncalves\LaravelSatis\Models\Packagist;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('Package implements PackageContract', function () {
    expect(is_a(Package::class, PackageContract::class, true))->toBeTrue();
});

it('Token implements TokenContract', function () {
    expect(is_a(Token::class, TokenContract::class, true))->toBeTrue();
});

it('Dependency implements DependencyContract', function () {
    expect(is_a(Dependency::class, DependencyContract::class, true))->toBeTrue();
});

it('PackageRelease implements PackageReleaseContract', function () {
    expect(is_a(PackageRelease::class, PackageReleaseContract::class, true))->toBeTrue();
});

it('PackageDownload implements PackageDownloadContract', function () {
    expect(is_a(PackageDownload::class, PackageDownloadContract::class, true))->toBeTrue();
});

it('DependencyPackageRelease implements DependencyPackageReleaseContract', function () {
    expect(is_a(DependencyPackageRelease::class, DependencyPackageReleaseContract::class, true))->toBeTrue();
});

it('PackageToken implements PackageTokenContract', function () {
    expect(is_a(PackageToken::class, PackageTokenContract::class, true))->toBeTrue();
});

it('Packagist implements PackagistContract', function () {
    expect(is_a(Packagist::class, PackagistContract::class, true))->toBeTrue();
});
