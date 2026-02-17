<?php

use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageDownload;

it('can create a package download', function () {
    $package = Package::factory()->create();
    $download = PackageDownload::create([
        'package_id' => $package->id,
        'version' => '1.0.0',
        'downloads' => 42,
    ]);

    expect($download)->toBeInstanceOf(PackageDownload::class)
        ->and($download->version)->toBe('1.0.0')
        ->and($download->downloads)->toBe(42);
});

it('uses the correct table name with prefix', function () {
    $download = new PackageDownload;

    expect($download->getTable())->toBe('satis_package_downloads');
});

it('uses table name without prefix when table_prefix is null', function () {
    config(['satis.table_prefix' => null]);

    $download = new PackageDownload;

    expect($download->getTable())->toBe('package_downloads');
});

it('casts downloads to integer', function () {
    $package = Package::factory()->create();
    $download = PackageDownload::create([
        'package_id' => $package->id,
        'version' => '1.0.0',
        'downloads' => '100',
    ]);

    expect($download->downloads)->toBeInt()->toBe(100);
});

it('belongs to a package', function () {
    $package = Package::factory()->create();
    $download = PackageDownload::create([
        'package_id' => $package->id,
        'version' => '1.0.0',
        'downloads' => 5,
    ]);

    expect($download->package)->toBeInstanceOf(Package::class)
        ->and($download->package->id)->toBe($package->id);
});
