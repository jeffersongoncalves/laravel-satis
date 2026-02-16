<?php

use JeffersonGoncalves\LaravelSatis\Actions\ProcessPackageFilename;

it('parses slash-separated package name', function () {
    $action = new ProcessPackageFilename;
    $result = $action->execute('vendor/package');

    expect($result)->toBe([
        'vendor' => 'vendor',
        'package' => 'package',
        'full_name' => 'vendor/package',
    ]);
});

it('parses dash-separated package name', function () {
    $action = new ProcessPackageFilename;
    $result = $action->execute('vendor-package');

    expect($result)->toBe([
        'vendor' => 'vendor',
        'package' => 'package',
        'full_name' => 'vendor/package',
    ]);
});

it('handles single-word package name', function () {
    $action = new ProcessPackageFilename;
    $result = $action->execute('packagename');

    expect($result)->toBe([
        'vendor' => null,
        'package' => 'packagename',
        'full_name' => 'packagename',
    ]);
});

it('handles vendor/package with extra path segments', function () {
    $action = new ProcessPackageFilename;
    $result = $action->execute('vendor/package/extra');

    expect($result['vendor'])->toBe('vendor')
        ->and($result['package'])->toBe('package')
        ->and($result['full_name'])->toBe('vendor/package');
});
