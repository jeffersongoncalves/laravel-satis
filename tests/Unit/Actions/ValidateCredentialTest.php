<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelSatis\Actions\ValidateCredential;
use JeffersonGoncalves\LaravelSatis\Models\Credential;

it('validates credential successfully when response is 200', function () {
    Http::fake([
        'repo.example.com/packages.json' => Http::response(['packages' => []], 200),
    ]);

    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user',
        'password' => 'pass',
    ]);

    $action = new ValidateCredential;
    $result = $action->execute($credential);

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Credential validated successfully.');

    $credential->refresh();
    expect($credential->is_validated)->toBeTrue()
        ->and($credential->validated_at)->not->toBeNull();
});

it('fails validation when response is not successful', function () {
    Http::fake([
        'repo.example.com/packages.json' => Http::response([], 401),
    ]);

    $credential = Credential::factory()->create([
        'url' => 'https://repo.example.com',
        'email' => 'user',
        'password' => 'wrong-pass',
    ]);

    $action = new ValidateCredential;
    $result = $action->execute($credential);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('401');

    $credential->refresh();
    expect($credential->is_validated)->toBeFalse()
        ->and($credential->validated_at)->toBeNull();
});

it('fails validation on connection error', function () {
    Http::fake([
        'nonexistent.example.com/*' => Http::response([], 500),
    ]);

    $credential = Credential::factory()->create([
        'url' => 'https://nonexistent.example.com',
        'email' => 'user',
        'password' => 'pass',
    ]);

    $action = new ValidateCredential;
    $result = $action->execute($credential);

    expect($result['success'])->toBeFalse();
});
