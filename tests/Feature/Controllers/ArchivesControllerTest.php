<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('serves archive file when token has license', function () {
    $token = Token::factory()->create();
    $package = Package::factory()->create(['name' => 'vendor/my-package']);
    $token->packages()->attach($package);

    Storage::fake('local');
    $storagePath = config('satis.storage_path', 'satis');
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/archives/vendor/my-package/my-package-1.0.0.zip',
        'fake-zip-content'
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->get('/'.config('satis.routes.composer_prefix').'/archives/vendor/my-package/my-package-1.0.0.zip');

    $response->assertOk();
});

it('serves archive when package is a private dependency', function () {
    $token = Token::factory()->create();
    Package::factory()->create(['name' => 'vendor/dep-package']);
    Dependency::create([
        'name' => 'vendor/dep-package',
        'type' => DependencyType::Private,
    ]);

    Storage::fake('local');
    $storagePath = config('satis.storage_path', 'satis');
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/archives/vendor/dep-package/dep-package-1.0.0.zip',
        'fake-zip-content'
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->get('/'.config('satis.routes.composer_prefix').'/archives/vendor/dep-package/dep-package-1.0.0.zip');

    $response->assertOk();
});

it('returns 403 when token does not have license', function () {
    $token = Token::factory()->create();
    Package::factory()->create(['name' => 'vendor/restricted-pkg']);

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->get('/'.config('satis.routes.composer_prefix').'/archives/vendor/restricted-pkg/restricted-pkg-1.0.0.zip');

    $response->assertForbidden();
});

it('returns 404 when package does not exist', function () {
    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->get('/'.config('satis.routes.composer_prefix').'/archives/vendor/nonexistent/nonexistent-1.0.0.zip');

    $response->assertNotFound();
});

it('returns 404 when archive file does not exist in storage', function () {
    $token = Token::factory()->create();
    $package = Package::factory()->create(['name' => 'vendor/my-package']);
    $token->packages()->attach($package);

    Storage::fake('local');

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->get('/'.config('satis.routes.composer_prefix').'/archives/vendor/my-package/my-package-1.0.0.zip');

    $response->assertNotFound();
});

it('requires authentication', function () {
    $response = $this->get('/'.config('satis.routes.composer_prefix').'/archives/vendor/package/package-1.0.0.zip');

    $response->assertUnauthorized();
});

it('rejects files that are not zip archives', function () {
    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->get('/'.config('satis.routes.composer_prefix').'/archives/vendor/package/file.txt');

    $response->assertNotFound();
});
