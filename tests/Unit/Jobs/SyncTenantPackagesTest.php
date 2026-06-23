<?php

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTenantPackages;
use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('groups packages by credential into separate satis builds', function () {
    Storage::fake('local');
    Process::fake();
    Sleep::fake();

    $credentialA = Credential::factory()->create();
    $credentialB = Credential::factory()->create();

    Package::factory()->create(['credential_id' => $credentialA->id]);
    Package::factory()->create(['credential_id' => $credentialA->id]);
    Package::factory()->create(['credential_id' => $credentialB->id]);

    (new SyncTenantPackages)->handle();

    // Two distinct credentials => two grouped builds => two satis processes.
    Process::assertRanTimes(fn () => true, 2);
});

it('builds an inline-auth url with rawurlencoded credentials', function () {
    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user@example.com',
        'password' => 'p@ss word',
    ]);

    $package = Package::factory()->create([
        'type' => PackageType::Composer,
        'credential_id' => $credential->id,
    ]);

    $job = new SyncTenantPackages;
    $method = new ReflectionMethod($job, 'buildInlineAuthUrl');
    $method->setAccessible(true);

    $url = $method->invoke($job, $package);

    expect($url)->toBe('https://user%40example.com:p%40ss%20word@repo.example.com');
});

it('returns the bare url when the credential has no email or password', function () {
    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => '',
        'password' => '',
    ]);

    $package = Package::factory()->create([
        'type' => PackageType::Composer,
        'credential_id' => $credential->id,
    ]);

    $job = new SyncTenantPackages;
    $method = new ReflectionMethod($job, 'buildInlineAuthUrl');
    $method->setAccessible(true);

    expect($method->invoke($job, $package))->toBe('https://repo.example.com');
});

it('writes an auth.json with http-basic credentials per host', function () {
    Storage::fake('local');
    Process::fake();
    Sleep::fake();

    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user@example.com',
        'password' => 'super-secret',
    ]);

    Package::factory()->create([
        'type' => PackageType::Composer,
        'credential_id' => $credential->id,
    ]);

    (new SyncTenantPackages)->handle();

    $disk = Storage::disk('local');
    $authPath = config('satis.storage_path', 'satis').'/composer/auth.json';

    expect($disk->exists($authPath))->toBeTrue();

    $auth = json_decode($disk->get($authPath), true);

    expect($auth['http-basic']['repo.example.com'])->toBe([
        'username' => 'user@example.com',
        'password' => 'super-secret',
    ]);
});

it('retries with increasing backoff when the build is rate limited', function () {
    Storage::fake('local');
    Sleep::fake();

    Process::fake([
        '*' => Process::result(exitCode: 1, errorOutput: 'fatal: HTTP 429 Too Many Requests'),
    ]);

    $credential = Credential::factory()->create();
    Package::factory()->create(['credential_id' => $credential->id]);

    (new SyncTenantPackages)->handle();

    // MAX_RETRIES = 3 attempts for the single group.
    Process::assertRanTimes(fn () => true, 3);

    // Backoff is RETRY_DELAY_SECONDS * attempt => 30s then 60s.
    Sleep::assertSequence([
        Sleep::for(30)->seconds(),
        Sleep::for(60)->seconds(),
    ]);
});
