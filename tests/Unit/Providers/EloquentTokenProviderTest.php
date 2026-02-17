<?php

use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\LaravelSatis\Models\Token;
use JeffersonGoncalves\LaravelSatis\Providers\EloquentTokenProvider;

beforeEach(function () {
    $this->provider = new EloquentTokenProvider(Token::class);
});

it('retrieves user by id', function () {
    $token = Token::factory()->create();

    $result = $this->provider->retrieveById($token->id);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($token->id);
});

it('returns null for non-existent id', function () {
    $result = $this->provider->retrieveById(999);

    expect($result)->toBeNull();
});

it('retrieves by credentials using token key', function () {
    $token = Token::factory()->create();

    $result = $this->provider->retrieveByCredentials(['token' => $token->token]);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($token->id);
});

it('retrieves by credentials using password key mapped to token', function () {
    $token = Token::factory()->create();

    $result = $this->provider->retrieveByCredentials(['password' => $token->token]);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($token->id);
});

it('returns null for empty credentials', function () {
    $result = $this->provider->retrieveByCredentials([]);

    expect($result)->toBeNull();
});

it('returns null for non-matching credentials', function () {
    Token::factory()->create();

    $result = $this->provider->retrieveByCredentials(['token' => 'non-existent-token']);

    expect($result)->toBeNull();
});

it('validates credentials always returns true', function () {
    $token = Token::factory()->create();

    expect($this->provider->validateCredentials($token, ['token' => $token->token]))->toBeTrue()
        ->and($this->provider->validateCredentials($token, ['token' => 'wrong-token']))->toBeTrue()
        ->and($this->provider->validateCredentials($token, []))->toBeTrue();
});

it('returns null for retrieveByToken', function () {
    $result = $this->provider->retrieveByToken('id', 'token');

    expect($result)->toBeNull();
});

it('creates model instance', function () {
    $model = $this->provider->createModel();

    expect($model)->toBeInstanceOf(Token::class);
});

it('gets and sets model', function () {
    expect($this->provider->getModel())->toBe(Token::class);

    $this->provider->setModel('App\\Models\\User');

    expect($this->provider->getModel())->toBe('App\\Models\\User');
});

it('supports query callback with withQuery', function () {
    $token = Token::factory()->create(['name' => 'Specific Token']);
    Token::factory()->create(['name' => 'Other Token']);

    $this->provider->withQuery(function (Builder $query) {
        $query->where('name', 'Specific Token');
    });

    $result = $this->provider->retrieveByCredentials(['token' => $token->token]);

    expect($result)->not->toBeNull()
        ->and($result->name)->toBe('Specific Token');
});

it('gets and sets query callback', function () {
    expect($this->provider->getQueryCallback())->toBeNull();

    $callback = fn (Builder $query) => $query->where('name', 'test');

    $this->provider->withQuery($callback);

    expect($this->provider->getQueryCallback())->toBe($callback);
});

it('clears query callback with null', function () {
    $this->provider->withQuery(fn (Builder $query) => $query);

    expect($this->provider->getQueryCallback())->not->toBeNull();

    $this->provider->withQuery(null);

    expect($this->provider->getQueryCallback())->toBeNull();
});

it('retrieves by credentials with array value', function () {
    Token::factory()->create(['name' => 'Token 1']);
    Token::factory()->create(['name' => 'Token 2']);

    $result = $this->provider->retrieveByCredentials(['name' => ['Token 1', 'Token 2']]);

    expect($result)->not->toBeNull()
        ->and($result->name)->toBeIn(['Token 1', 'Token 2']);
});

it('retrieves by credentials with closure value', function () {
    Token::factory()->create(['name' => 'Closure Token']);

    $result = $this->provider->retrieveByCredentials([
        'name' => fn (Builder $query) => $query->where('name', 'Closure Token'),
    ]);

    expect($result)->not->toBeNull()
        ->and($result->name)->toBe('Closure Token');
});
