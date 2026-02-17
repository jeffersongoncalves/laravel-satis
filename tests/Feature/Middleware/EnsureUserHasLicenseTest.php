<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('authenticates with basic auth password', function () {
    Bus::fake();

    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => 'composer',
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertOk();
});

it('authenticates with bearer token', function () {
    Bus::fake();

    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertOk();
});

it('rejects request without credentials', function () {
    $response = $this->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertUnauthorized()
        ->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Invalid token credentials.',
        ]);
});

it('rejects request with invalid token', function () {
    $response = $this->withHeaders([
        'PHP_AUTH_USER' => 'composer',
        'PHP_AUTH_PW' => 'invalid-token-string',
    ])->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertUnauthorized();
});
