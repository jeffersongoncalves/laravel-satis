<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('returns 404 when no build exists', function () {
    $token = Token::factory()->create();

    Storage::fake('local');

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertNotFound();
});

it('returns packages.json content from storage', function () {
    $token = Token::factory()->create();

    Storage::fake('local');
    $storagePath = config('satis.storage_path', 'satis');
    $packagesContent = json_encode([
        'packages' => ['vendor/package' => ['1.0.0' => []]],
        'notify-batch' => '/api/satis/composer/downloads',
    ]);
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/packages.json',
        $packagesContent
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertOk()
        ->assertJsonStructure(['packages', 'notify-batch']);
});

it('requires authentication', function () {
    $response = $this->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertUnauthorized();
});

it('rejects invalid token', function () {
    $response = $this->withHeaders([
        'PHP_AUTH_USER' => 'token',
        'PHP_AUTH_PW' => 'invalid-token',
    ])->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    $response->assertUnauthorized();
});

it('serves routes without prefix when composer_prefix is null', function () {
    config(['satis.routes.composer_prefix' => null]);

    $routeFile = dirname(__DIR__, 3).'/routes/composer.php';
    require $routeFile;

    $token = Token::factory()->create();

    Storage::fake('local');
    $storagePath = config('satis.storage_path', 'satis');
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/packages.json',
        json_encode(['packages' => []])
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/packages.json');

    $response->assertOk()
        ->assertJsonStructure(['packages']);
});
