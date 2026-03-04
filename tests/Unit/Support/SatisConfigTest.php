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
        ->toHaveKey('notify-batch')
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

it('includes basic auth options for github packages with credentials', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Github,
            'url' => 'https://github.com/vendor/package-a.git',
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

it('includes basic auth options for composer packages with credentials', function () {
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

it('makes URLs unique for same-url packages with different credentials', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'url' => 'https://packages.example.com',
            'username' => 'license-1',
            'password' => 'secret-1',
        ]),
        Package::factory()->make([
            'name' => 'vendor/package-b',
            'type' => PackageType::Composer,
            'url' => 'https://packages.example.com',
            'username' => 'license-2',
            'password' => 'secret-2',
        ]),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['repositories'])->toHaveCount(2)
        ->and($config['repositories'][0]['url'])->toBe('https://packages.example.com')
        ->and($config['repositories'][1]['url'])->toBe('https://packages.example.com/.')
        ->and($config['repositories'][0]['options']['http']['header'][0])
        ->toBe('Authorization: Basic '.base64_encode('license-1:secret-1'))
        ->and($config['repositories'][1]['options']['http']['header'][0])
        ->toBe('Authorization: Basic '.base64_encode('license-2:secret-2'));

    expect($config['require'])->toBe([
        'vendor/package-a' => '*',
        'vendor/package-b' => '*',
    ]);
});

it('deduplicates repos when same url and same credentials', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'url' => 'https://packages.example.com',
            'username' => 'user',
            'password' => 'pass',
        ]),
        Package::factory()->make([
            'name' => 'vendor/package-b',
            'type' => PackageType::Composer,
            'url' => 'https://packages.example.com',
            'username' => 'user',
            'password' => 'pass',
        ]),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['repositories'])->toHaveCount(1)
        ->and($config['repositories'][0]['url'])->toBe('https://packages.example.com');

    expect($config['require'])->toBe([
        'vendor/package-a' => '*',
        'vendor/package-b' => '*',
    ]);
});

it('handles three credential sets on same url', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'url' => 'https://packages.example.com',
            'username' => 'user-1',
            'password' => 'pass-1',
        ]),
        Package::factory()->make([
            'name' => 'vendor/package-b',
            'type' => PackageType::Composer,
            'url' => 'https://packages.example.com',
            'username' => 'user-2',
            'password' => 'pass-2',
        ]),
        Package::factory()->make([
            'name' => 'vendor/package-c',
            'type' => PackageType::Composer,
            'url' => 'https://packages.example.com',
            'username' => 'user-3',
            'password' => 'pass-3',
        ]),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['repositories'])->toHaveCount(3)
        ->and($config['repositories'][0]['url'])->toBe('https://packages.example.com')
        ->and($config['repositories'][1]['url'])->toBe('https://packages.example.com/.')
        ->and($config['repositories'][2]['url'])->toBe('https://packages.example.com/./.');
});

it('deduplicates repos without credentials sharing same url', function () {
    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'url' => 'https://repo.example.com',
            'username' => null,
            'password' => null,
        ]),
        Package::factory()->make([
            'name' => 'vendor/package-b',
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
        ->and($config['repositories'][0]['url'])->toBe('https://repo.example.com');

    expect($config['require'])->toBe([
        'vendor/package-a' => '*',
        'vendor/package-b' => '*',
    ]);
});
