<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Models\Token;
use JeffersonGoncalves\LaravelSatis\Providers\EloquentTokenProvider;

beforeEach(function () {
    Bus::fake();
    $this->provider = new EloquentTokenProvider(
        app('hash'),
        Token::class
    );
});

it('retrieves user by id (token field)', function () {
    $token = Token::factory()->create();

    $result = $this->provider->retrieveById($token->token);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($token->id);
});

it('returns null for non-existent id', function () {
    $result = $this->provider->retrieveById('non-existent');

    expect($result)->toBeNull();
});

it('retrieves by credentials using token key', function () {
    $token = Token::factory()->create();

    $result = $this->provider->retrieveByCredentials(['token' => $token->token]);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($token->id);
});

it('retrieves by credentials using password key', function () {
    $token = Token::factory()->create();

    $result = $this->provider->retrieveByCredentials(['password' => $token->token]);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($token->id);
});

it('returns null for empty credentials', function () {
    $result = $this->provider->retrieveByCredentials([]);

    expect($result)->toBeNull();
});

it('validates credentials correctly', function () {
    $token = Token::factory()->create();

    expect($this->provider->validateCredentials($token, ['token' => $token->token]))->toBeTrue()
        ->and($this->provider->validateCredentials($token, ['token' => 'wrong-token']))->toBeFalse();
});

it('validates credentials using password key', function () {
    $token = Token::factory()->create();

    expect($this->provider->validateCredentials($token, ['password' => $token->token]))->toBeTrue();
});

it('returns false when no token in credentials', function () {
    $token = Token::factory()->create();

    expect($this->provider->validateCredentials($token, []))->toBeFalse();
});

it('returns null for retrieveByToken', function () {
    $result = $this->provider->retrieveByToken('id', 'token');

    expect($result)->toBeNull();
});
