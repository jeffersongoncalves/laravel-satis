<?php

use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('returns column codes for package', function () {
    expect(Package::getColumnCode())->toBe(['webhook_secret', 'reference']);
});

it('returns column codes for token', function () {
    expect(Token::getColumnCode())->toBe(['token']);
});

it('returns length codes for package', function () {
    expect(Package::getLengthCode())->toBe([
        'webhook_secret' => 40,
        'reference' => 20,
    ]);
});

it('returns length codes for token', function () {
    expect(Token::getLengthCode())->toBe([
        'token' => 64,
    ]);
});

it('returns default length for unknown column', function () {
    expect(Package::getLengthCodeByColumn('unknown'))->toBe(8);
});

it('generates token with 64 characters', function () {
    $token = Token::generateCode('token');

    expect($token)->toBeString()
        ->and(strlen($token))->toBe(64);
});

it('generates webhook secret with 40 characters', function () {
    $secret = Package::generateCode('webhook_secret');

    expect(strlen($secret))->toBe(40);
});

it('generates reference with 20 characters', function () {
    $reference = Package::generateCode('reference');

    expect(strlen($reference))->toBe(20);
});

it('generates unique codes on each call', function () {
    $codes = collect(range(1, 10))->map(fn () => Package::generateCode('reference'));

    expect($codes->unique()->count())->toBe(10);
});
