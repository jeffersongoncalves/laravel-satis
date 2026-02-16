<?php

use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('can create a token', function () {
    $token = Token::factory()->create([
        'name' => 'My Token',
        'email' => 'user@example.com',
    ]);

    expect($token)->toBeInstanceOf(Token::class)
        ->and($token->name)->toBe('My Token')
        ->and($token->email)->toBe('user@example.com');
});

it('uses the correct table name with prefix', function () {
    $token = new Token;

    expect($token->getTable())->toBe('satis_tokens');
});

it('hides token in array representation', function () {
    $token = Token::factory()->create();

    $array = $token->toArray();

    expect($array)->not->toHaveKey('token');
});

it('auto-generates token on creation', function () {
    $token = Token::factory()->create(['token' => null]);

    expect($token->token)->not->toBeNull()
        ->and(strlen($token->token))->toBe(64);
});

it('has a packages relationship', function () {
    $token = Token::factory()->create();
    $package = Package::factory()->create();

    $token->packages()->attach($package->id);

    expect($token->packages)->toHaveCount(1)
        ->and($token->packages->first()->id)->toBe($package->id);
});

it('returns token as auth identifier', function () {
    $token = Token::factory()->create();

    expect($token->getAuthIdentifierName())->toBe('token')
        ->and($token->getAuthIdentifier())->toBe($token->token)
        ->and($token->getAuthPassword())->toBe($token->token);
});
