<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('returns include file content from storage', function () {
    $token = Token::factory()->create();

    Storage::fake('local');
    $storagePath = config('satis.storage_path', 'satis');
    $includeContent = json_encode([
        'packages' => ['vendor/package' => ['1.0.0' => ['name' => 'vendor/package']]],
    ]);
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/include/all$abc123.json',
        $includeContent
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/include/all$abc123.json');

    $response->assertOk()
        ->assertJsonStructure(['packages']);
});

it('returns 404 when include file does not exist', function () {
    $token = Token::factory()->create();

    Storage::fake('local');

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/include/nonexistent.json');

    $response->assertNotFound();
});

it('requires authentication', function () {
    $response = $this->getJson('/'.config('satis.routes.composer_prefix').'/include/all$abc123.json');

    $response->assertUnauthorized();
});
