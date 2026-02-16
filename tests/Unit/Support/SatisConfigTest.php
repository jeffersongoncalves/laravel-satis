<?php

use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Support\SatisConfig;

it('can be created via make()', function () {
    $config = SatisConfig::make();

    expect($config)->toBeInstanceOf(SatisConfig::class);
});

it('generates config with default values', function () {
    $config = SatisConfig::make()->toArray();

    expect($config)->toHaveKey('name')
        ->toHaveKey('homepage')
        ->toHaveKey('repositories')
        ->toHaveKey('require');
});

it('sets homepage', function () {
    $config = SatisConfig::make()
        ->setHomepage('https://satis.example.com')
        ->toArray();

    expect($config['homepage'])->toBe('https://satis.example.com');
});

it('builds repositories from packages', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'url' => 'https://repo.example.com',
            'username' => null,
            'password' => null,
        ]),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['repositories'])->toHaveCount(1)
        ->and($config['repositories'][0]['type'])->toBe('composer')
        ->and($config['repositories'][0]['url'])->toBe('https://repo.example.com');
});

it('maps github type to vcs', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Github,
            'url' => 'https://github.com/vendor/package-a.git',
            'username' => null,
            'password' => null,
        ]),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['repositories'][0]['type'])->toBe('vcs');
});

it('includes basic auth when credentials are set', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'url' => 'https://repo.example.com',
            'username' => 'user',
            'password' => 'pass',
        ]),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    $repo = $config['repositories'][0];

    expect($repo)->toHaveKey('options')
        ->and($repo['options']['http']['header'][0])
        ->toBe('Authorization: Basic '.base64_encode('user:pass'));
});

it('builds require list from packages', function () {
    $packages = collect([
        Package::factory()->make(['name' => 'vendor/package-a']),
        Package::factory()->make(['name' => 'vendor/package-b']),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['require'])->toBe([
        'vendor/package-a' => '*',
        'vendor/package-b' => '*',
    ]);
});

it('converts to JSON', function () {
    $json = SatisConfig::make()->toJson();

    expect($json)->toBeString()
        ->and(json_decode($json, true))->toBeArray();
});
