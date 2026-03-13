<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Jobs\ValidatePackageCredentialsJob;
use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('generates webhook_secret on creation when github type and empty', function () {
    $package = Package::factory()->github()->create(['webhook_secret' => null]);

    expect($package->webhook_secret)->not->toBeNull()
        ->and(strlen($package->webhook_secret))->toBe(64);
});

it('does not generate webhook_secret on creation when composer type', function () {
    $package = Package::factory()->create(['webhook_secret' => null]);

    expect($package->webhook_secret)->toBeNull();
});

it('generates reference on creation when empty', function () {
    $package = Package::factory()->create(['reference' => null]);

    expect($package->reference)->not->toBeNull()
        ->and(strlen($package->reference))->toBe(32);
});

it('preserves existing webhook_secret on creation', function () {
    $package = Package::factory()->github()->create(['webhook_secret' => 'my-custom-secret']);

    expect($package->webhook_secret)->toBe('my-custom-secret');
});

it('preserves existing reference on creation', function () {
    $package = Package::factory()->create(['reference' => 'my-custom-ref']);

    expect($package->reference)->toBe('my-custom-ref');
});

it('dispatches ValidatePackageCredentialsJob on creation', function () {
    Bus::fake();

    Package::factory()->create();

    Bus::assertDispatched(ValidatePackageCredentialsJob::class);
});

it('clears credentials validation when credential_id changes', function () {
    $package = Package::factory()->validated()->create();
    $newCredential = Credential::factory()->create();

    expect($package->is_credentials_validated)->toBeTrue();

    $package->update(['credential_id' => $newCredential->id]);
    $package->refresh();

    expect($package->is_credentials_validated)->toBeFalse()
        ->and($package->credentials_validated_at)->toBeNull();
});

it('dispatches SyncTokenPackages for linked tokens when credentials are validated', function () {
    Bus::fake();

    $package = Package::factory()->create();
    $token = Token::factory()->create();
    $package->tokens()->attach($token);

    Bus::assertDispatchedTimes(SyncTokenPackages::class, 1);

    $package->update([
        'is_credentials_validated' => true,
        'credentials_validated_at' => now(),
    ]);

    Bus::assertDispatchedTimes(SyncTokenPackages::class, 2);
});

it('does not dispatch SyncTokenPackages when credentials validation is set to false', function () {
    Bus::fake();

    $package = Package::factory()->validated()->create();
    $token = Token::factory()->create();
    $package->tokens()->attach($token);

    Bus::assertDispatchedTimes(SyncTokenPackages::class, 1);

    $package->update([
        'is_credentials_validated' => false,
        'credentials_validated_at' => null,
    ]);

    Bus::assertDispatchedTimes(SyncTokenPackages::class, 1);
});
