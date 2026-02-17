<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('dispatches SyncTokenPackages for all tokens with packages', function () {
    $token1 = Token::factory()->create();
    $token2 = Token::factory()->create();
    $tokenWithout = Token::factory()->create();

    $package = Package::factory()->create();
    $token1->packages()->attach($package->id);
    $token2->packages()->attach($package->id);

    Bus::fake();

    $this->artisan('satis:token-build')
        ->expectsOutputToContain('Dispatched build for 2 tokens.')
        ->assertExitCode(0);

    Bus::assertDispatched(SyncTokenPackages::class, 2);
});

it('dispatches SyncTokenPackages for specific token', function () {
    $token = Token::factory()->create();
    $package = Package::factory()->create();
    $token->packages()->attach($package->id);

    Bus::fake();

    $this->artisan('satis:token-build', ['--token' => $token->id])
        ->expectsOutputToContain("Dispatched build for token: {$token->id}")
        ->assertExitCode(0);

    Bus::assertDispatched(SyncTokenPackages::class, 1);
});

it('fails when specified token does not exist', function () {
    Bus::fake();

    $this->artisan('satis:token-build', ['--token' => 999])
        ->expectsOutputToContain('Token [999] not found.')
        ->assertExitCode(1);

    Bus::assertNotDispatched(SyncTokenPackages::class);
});

it('warns when no tokens with packages exist', function () {
    Token::factory()->create();

    Bus::fake();

    $this->artisan('satis:token-build')
        ->expectsOutputToContain('No tokens with packages found.')
        ->assertExitCode(0);

    Bus::assertNotDispatched(SyncTokenPackages::class);
});
