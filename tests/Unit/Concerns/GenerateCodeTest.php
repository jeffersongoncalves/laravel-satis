<?php

use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('generates unique code with default length', function () {
    $code = Package::generateUniqueCode();

    expect($code)->toBeString()
        ->and(strlen($code))->toBe(32);
});

it('generates unique code with custom length', function () {
    $code = Package::generateUniqueCode(16);

    expect(strlen($code))->toBe(16);
});

it('generates token with 64 characters', function () {
    $token = Token::generateToken();

    expect(strlen($token))->toBe(64);
});

it('generates webhook secret with 40 characters', function () {
    $secret = Package::generateWebhookSecret();

    expect(strlen($secret))->toBe(40);
});

it('generates reference with 20 characters', function () {
    $reference = Package::generateReference();

    expect(strlen($reference))->toBe(20);
});

it('generates unique codes on each call', function () {
    $codes = collect(range(1, 10))->map(fn () => Package::generateUniqueCode());

    expect($codes->unique()->count())->toBe(10);
});
