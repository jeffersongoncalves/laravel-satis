<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('generates token on creation when empty', function () {
    $token = Token::factory()->create(['token' => null]);

    expect($token->token)->not->toBeNull()
        ->and(strlen($token->token))->toBe(64);
});

it('preserves existing token on creation', function () {
    $token = Token::factory()->create(['token' => 'my-custom-token']);

    expect($token->token)->toBe('my-custom-token');
});

it('dispatches SyncTokenPackages on creation', function () {
    Bus::fake();

    Token::factory()->create();

    Bus::assertDispatched(SyncTokenPackages::class);
});
