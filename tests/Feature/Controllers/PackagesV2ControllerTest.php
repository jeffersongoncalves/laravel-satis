<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('returns p2 package metadata when token has license', function () {
    $token = Token::factory()->create();
    $package = Package::factory()->create(['name' => 'vendor/my-package']);
    $token->packages()->attach($package);

    Storage::fake('local');
    $storagePath = config('satis.storage_path', 'satis');
    $content = json_encode([
        'packages' => ['vendor/my-package' => ['1.0.0' => ['name' => 'vendor/my-package']]],
    ]);
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/p2/vendor/my-package.json',
        $content
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/my-package.json');

    $response->assertOk()
        ->assertJsonStructure(['packages']);
});

it('returns p2 package metadata when package is a private dependency', function () {
    $token = Token::factory()->create();
    Package::factory()->create(['name' => 'vendor/dep-package']);
    Dependency::create([
        'name' => 'vendor/dep-package',
        'type' => DependencyType::Private,
    ]);

    Storage::fake('local');
    $storagePath = config('satis.storage_path', 'satis');
    $content = json_encode([
        'packages' => ['vendor/dep-package' => ['1.0.0' => []]],
    ]);
    Storage::disk('local')->put(
        $storagePath.'/'.$token->id.'/p2/vendor/dep-package.json',
        $content
    );

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/dep-package.json');

    $response->assertOk();
});

it('returns 403 when token does not have license', function () {
    $token = Token::factory()->create();
    Package::factory()->create(['name' => 'vendor/restricted-pkg']);

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/restricted-pkg.json');

    $response->assertForbidden();
});

it('returns 404 when package does not exist', function () {
    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/nonexistent.json');

    $response->assertNotFound();
});

it('returns 404 when p2 file does not exist in storage', function () {
    $token = Token::factory()->create();
    $package = Package::factory()->create(['name' => 'vendor/my-package']);
    $token->packages()->attach($package);

    Storage::fake('local');

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/my-package.json');

    $response->assertNotFound();
});

it('requires authentication', function () {
    $response = $this->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/package.json');

    $response->assertUnauthorized();
});
