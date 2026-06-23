<?php

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('groups token packages by credential into separate satis builds', function () {
    Storage::fake('local');
    Process::fake();
    Sleep::fake();

    $token = Token::factory()->create();

    $credentialA = Credential::factory()->create();
    $credentialB = Credential::factory()->create();

    $packageA1 = Package::factory()->create(['credential_id' => $credentialA->id]);
    $packageA2 = Package::factory()->create(['credential_id' => $credentialA->id]);
    $packageB1 = Package::factory()->create(['credential_id' => $credentialB->id]);

    $token->packages()->attach([$packageA1->id, $packageA2->id, $packageB1->id]);

    (new SyncTokenPackages($token))->handle();

    Process::assertRanTimes(fn () => true, 2);
});

it('builds an inline-auth url with rawurlencoded credentials', function () {
    $token = Token::factory()->create();

    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user@example.com',
        'password' => 'p@ss word',
    ]);

    $package = Package::factory()->create([
        'type' => PackageType::Composer,
        'credential_id' => $credential->id,
    ]);

    $job = new SyncTokenPackages($token);
    $method = new ReflectionMethod($job, 'buildInlineAuthUrl');
    $method->setAccessible(true);

    $url = $method->invoke($job, $package);

    expect($url)->toBe('https://user%40example.com:p%40ss%20word@repo.example.com');
});

it('writes an auth.json with http-basic credentials per host', function () {
    Storage::fake('local');
    Process::fake();
    Sleep::fake();

    $token = Token::factory()->create();

    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user@example.com',
        'password' => 'super-secret',
    ]);

    $package = Package::factory()->create([
        'type' => PackageType::Composer,
        'credential_id' => $credential->id,
    ]);

    $token->packages()->attach($package->id);

    (new SyncTokenPackages($token))->handle();

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

    $token = Token::factory()->create();
    $credential = Credential::factory()->create();
    $package = Package::factory()->create(['credential_id' => $credential->id]);

    $token->packages()->attach($package->id);

    (new SyncTokenPackages($token))->handle();

    Process::assertRanTimes(fn () => true, 3);

    Sleep::assertSequence([
        Sleep::for(30)->seconds(),
        Sleep::for(60)->seconds(),
    ]);
});
