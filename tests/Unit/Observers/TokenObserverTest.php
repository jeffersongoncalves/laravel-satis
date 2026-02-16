<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
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

it('clears cache on token creation', function () {
    Bus::fake();

    Cache::put('laravel-satis:tokens', 'cached-data');
    Cache::put('laravel-satis:tokens-count', 5);

    Token::factory()->create();

    expect(Cache::get('laravel-satis:tokens'))->toBeNull()
        ->and(Cache::get('laravel-satis:tokens-count'))->toBeNull();
});

it('clears cache on token deletion', function () {
    Bus::fake();

    $token = Token::factory()->create();

    Cache::put('laravel-satis:tokens', 'cached-data');
    $token->delete();

    expect(Cache::get('laravel-satis:tokens'))->toBeNull();
});
