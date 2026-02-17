<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\CreateAuthJson;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('creates auth.json with http-basic credentials for composer packages', function () {
    Storage::fake('local');

    $package = Package::factory()->create([
        'type' => PackageType::Composer,
        'url' => 'https://repo.example.com',
        'username' => 'my-user',
        'password' => 'my-pass',
    ]);

    $packages = collect([$package]);

    $action = new CreateAuthJson;
    $composerHome = $action->execute($packages, 'satis/composer');

    $disk = Storage::disk('local');
    expect($disk->exists('satis/composer/auth.json'))->toBeTrue();

    $authJson = json_decode($disk->get('satis/composer/auth.json'), true);

    expect($authJson['http-basic'])->toHaveKey('repo.example.com')
        ->and($authJson['http-basic']['repo.example.com']['username'])->toBe('my-user')
        ->and($authJson['http-basic']['repo.example.com']['password'])->toBe('my-pass');
});

it('skips github packages in auth.json', function () {
    Storage::fake('local');

    $composerPackage = Package::factory()->create([
        'type' => PackageType::Composer,
        'url' => 'https://repo.example.com',
        'username' => 'user',
        'password' => 'pass',
    ]);

    $githubPackage = Package::factory()->github()->create([
        'username' => 'gh-user',
        'password' => 'gh-token',
    ]);

    $packages = collect([$composerPackage, $githubPackage]);

    $action = new CreateAuthJson;
    $action->execute($packages, 'satis/composer');

    $disk = Storage::disk('local');
    $authJson = json_decode($disk->get('satis/composer/auth.json'), true);

    expect($authJson['http-basic'])->toHaveCount(1)
        ->toHaveKey('repo.example.com');
});

it('creates empty auth.json when no composer packages', function () {
    Storage::fake('local');

    $packages = collect();

    $action = new CreateAuthJson;
    $action->execute($packages, 'satis/composer');

    $disk = Storage::disk('local');
    $authJson = json_decode($disk->get('satis/composer/auth.json'), true);

    expect($authJson['http-basic'])->toBeEmpty();
});

it('handles multiple composer packages from different hosts', function () {
    Storage::fake('local');

    $package1 = Package::factory()->create([
        'type' => PackageType::Composer,
        'url' => 'https://repo1.example.com',
        'username' => 'user1',
        'password' => 'pass1',
    ]);

    $package2 = Package::factory()->create([
        'type' => PackageType::Composer,
        'url' => 'https://repo2.example.com',
        'username' => 'user2',
        'password' => 'pass2',
    ]);

    $packages = collect([$package1, $package2]);

    $action = new CreateAuthJson;
    $action->execute($packages, 'satis/composer');

    $disk = Storage::disk('local');
    $authJson = json_decode($disk->get('satis/composer/auth.json'), true);

    expect($authJson['http-basic'])->toHaveCount(2)
        ->toHaveKey('repo1.example.com')
        ->toHaveKey('repo2.example.com');
});
