<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('returns empty packages when no build exists', function () {
    Bus::fake();

    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => 'composer',
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('laravel-satis.routes.composer_prefix').'/packages.json');

    $response->assertOk()
        ->assertJsonStructure(['packages', 'notify-batch']);
});

it('returns packages.json content from storage', function () {
    Bus::fake();

    $token = Token::factory()->create();

    Storage::fake('local');
    $storagePath = config('laravel-satis.storage_path', 'satis');
    $packagesContent = json_encode([
        'packages' => ['vendor/package' => ['1.0.0' => []]],
    ]);
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/packages.json',
        $packagesContent
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => 'composer',
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('laravel-satis.routes.composer_prefix').'/packages.json');

    $response->assertOk()
        ->assertJsonStructure(['packages', 'notify-batch']);
});

it('requires authentication', function () {
    $response = $this->getJson('/'.config('laravel-satis.routes.composer_prefix').'/packages.json');

    $response->assertUnauthorized();
});

it('rejects invalid token', function () {
    $response = $this->withHeaders([
        'PHP_AUTH_USER' => 'composer',
        'PHP_AUTH_PW' => 'invalid-token',
    ])->getJson('/'.config('laravel-satis.routes.composer_prefix').'/packages.json');

    $response->assertUnauthorized();
});
