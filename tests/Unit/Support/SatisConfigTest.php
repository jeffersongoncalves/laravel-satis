<?php

use JeffersonGoncalves\LaravelSatis\Data\PackageData;
use JeffersonGoncalves\LaravelSatis\Data\RepositoryData;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Credential;
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
        ->homepage('https://satis.example.com')
        ->toArray();

    expect($config['homepage'])->toBe('https://satis.example.com');
});

it('implements Stringable', function () {
    $config = SatisConfig::make();

    expect((string) $config)->toBeString()
        ->and(json_decode((string) $config, true))->toBeArray();
});

it('adds repositories via repository method', function () {
    $config = SatisConfig::make()
        ->repository(new RepositoryData(name: 'vendor/pkg', type: 'composer', url: 'https://repo.example.com'))
        ->require(new PackageData(name: 'vendor/pkg'))
        ->toArray();

    expect($config['repositories'])->toHaveCount(1)
        ->and($config['repositories'][0]['type'])->toBe('composer')
        ->and($config['repositories'][0]['url'])->toBe('https://repo.example.com')
        ->and($config['require'])->toBe(['vendor/pkg' => '*']);
});

it('adds http-basic to config', function () {
    $config = SatisConfig::make()
        ->httpBasic('repo.example.com', 'user', 'pass')
        ->toArray();

    expect($config['config']['http-basic']['repo.example.com'])->toBe([
        'username' => 'user',
        'password' => 'pass',
    ]);
});

it('builds repositories from packages via setPackages', function () {
    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user',
        'password' => 'pass',
    ]);

    $packages = collect([
        Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'credential_id' => $credential->id,
        ]),
    ]);

    // Manually set the credential relation to avoid DB lookup on make()
    $packages->first()->setRelation('credential', $credential);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['repositories'])->toHaveCount(1)
        ->and($config['repositories'][0]['type'])->toBe('composer')
        ->and($config['repositories'][0]['url'])->toBe('https://repo.example.com');
});

it('maps github type to vcs', function () {
    $credential = Credential::factory()->create([
        'url' => 'https://github.com/vendor/package-a.git',
        'email' => '',
        'password' => '',
    ]);

    $package = Package::factory()->make([
        'name' => 'vendor/package-a',
        'type' => PackageType::Github,
        'credential_id' => $credential->id,
    ]);
    $package->setRelation('credential', $credential);

    $config = SatisConfig::make()
        ->setPackages(collect([$package]))
        ->toArray();

    expect($config['repositories'][0]['type'])->toBe('vcs');
});

it('includes basic auth options for github packages with credentials', function () {
    $credential = Credential::factory()->create([
        'url' => 'https://github.com/vendor/package-a.git',
        'email' => 'user',
        'password' => 'pass',
    ]);

    $package = Package::factory()->make([
        'name' => 'vendor/package-a',
        'type' => PackageType::Github,
        'credential_id' => $credential->id,
    ]);
    $package->setRelation('credential', $credential);

    $config = SatisConfig::make()
        ->setPackages(collect([$package]))
        ->toArray();

    $repo = $config['repositories'][0];

    expect($repo)->toHaveKey('options')
        ->and($repo['options']['http']['header'][0])
        ->toBe('Authorization: Basic '.base64_encode('user:pass'));
});

it('does not add auth options for composer packages without credential conflicts', function () {
    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user',
        'password' => 'pass',
    ]);

    $package = Package::factory()->make([
        'name' => 'vendor/package-a',
        'type' => PackageType::Composer,
        'credential_id' => $credential->id,
    ]);
    $package->setRelation('credential', $credential);

    $config = SatisConfig::make()
        ->setPackages(collect([$package]))
        ->toArray();

    $repo = $config['repositories'][0];

    expect($repo)->not->toHaveKey('options');
});

it('builds require list from packages', function () {
    $credential = Credential::factory()->create();

    $packages = collect([
        tap(Package::factory()->make(['name' => 'vendor/package-a', 'credential_id' => $credential->id]), fn ($p) => $p->setRelation('credential', $credential)),
        tap(Package::factory()->make(['name' => 'vendor/package-b', 'credential_id' => $credential->id]), fn ($p) => $p->setRelation('credential', $credential)),
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
    $cred1 = Credential::factory()->create([
        'url' => 'https://packages.example.com',
        'email' => 'license-1',
        'password' => 'secret-1',
    ]);
    $cred2 = Credential::factory()->create([
        'url' => 'https://packages.example.com',
        'email' => 'license-2',
        'password' => 'secret-2',
    ]);

    $packages = collect([
        tap(Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'credential_id' => $cred1->id,
        ]), fn ($p) => $p->setRelation('credential', $cred1)),
        tap(Package::factory()->make([
            'name' => 'vendor/package-b',
            'type' => PackageType::Composer,
            'credential_id' => $cred2->id,
        ]), fn ($p) => $p->setRelation('credential', $cred2)),
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
    $credential = Credential::factory()->create([
        'url' => 'https://packages.example.com',
        'email' => 'user',
        'password' => 'pass',
    ]);

    $packages = collect([
        tap(Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'credential_id' => $credential->id,
        ]), fn ($p) => $p->setRelation('credential', $credential)),
        tap(Package::factory()->make([
            'name' => 'vendor/package-b',
            'type' => PackageType::Composer,
            'credential_id' => $credential->id,
        ]), fn ($p) => $p->setRelation('credential', $credential)),
    ]);

    $config = SatisConfig::make()
        ->setPackages($packages)
        ->toArray();

    expect($config['repositories'])->toHaveCount(1)
        ->and($config['repositories'][0]['url'])->toBe('https://packages.example.com')
        ->and($config['repositories'][0])->not->toHaveKey('options');

    expect($config['require'])->toBe([
        'vendor/package-a' => '*',
        'vendor/package-b' => '*',
    ]);
});

it('handles three credential sets on same url', function () {
    $cred1 = Credential::factory()->create([
        'url' => 'https://packages.example.com',
        'email' => 'user-1',
        'password' => 'pass-1',
    ]);
    $cred2 = Credential::factory()->create([
        'url' => 'https://packages.example.com',
        'email' => 'user-2',
        'password' => 'pass-2',
    ]);
    $cred3 = Credential::factory()->create([
        'url' => 'https://packages.example.com',
        'email' => 'user-3',
        'password' => 'pass-3',
    ]);

    $packages = collect([
        tap(Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'credential_id' => $cred1->id,
        ]), fn ($p) => $p->setRelation('credential', $cred1)),
        tap(Package::factory()->make([
            'name' => 'vendor/package-b',
            'type' => PackageType::Composer,
            'credential_id' => $cred2->id,
        ]), fn ($p) => $p->setRelation('credential', $cred2)),
        tap(Package::factory()->make([
            'name' => 'vendor/package-c',
            'type' => PackageType::Composer,
            'credential_id' => $cred3->id,
        ]), fn ($p) => $p->setRelation('credential', $cred3)),
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
    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => '',
        'password' => '',
    ]);

    $packages = collect([
        tap(Package::factory()->make([
            'name' => 'vendor/package-a',
            'type' => PackageType::Composer,
            'credential_id' => $credential->id,
        ]), fn ($p) => $p->setRelation('credential', $credential)),
        tap(Package::factory()->make([
            'name' => 'vendor/package-b',
            'type' => PackageType::Composer,
            'credential_id' => $credential->id,
        ]), fn ($p) => $p->setRelation('credential', $credential)),
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
