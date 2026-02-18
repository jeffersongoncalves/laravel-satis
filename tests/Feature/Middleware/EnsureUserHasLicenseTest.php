<?php

use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('allows access when token has package license', function () {
    $token = Token::factory()->create();
    $package = Package::factory()->create(['name' => 'vendor/licensed-pkg']);
    $token->packages()->attach($package);

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/licensed-pkg.json');

    $response->assertNotFound(); // 404 because the p2 file doesn't exist in storage, but middleware passed
});

it('allows access when package is a private dependency', function () {
    $token = Token::factory()->create();
    Package::factory()->create(['name' => 'vendor/dep-package']);
    Dependency::create([
        'name' => 'vendor/dep-package',
        'type' => DependencyType::Private,
    ]);

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/dep-package.json');

    $response->assertNotFound(); // 404 because the p2 file doesn't exist, but middleware passed
});

it('denies access when token does not have license for non-private dependency', function () {
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

it('requires authentication via basic auth', function () {
    $response = $this->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/package.json');

    $response->assertUnauthorized();
});

it('rejects invalid token credentials', function () {
    $response = $this->withHeaders([
        'PHP_AUTH_USER' => 'token',
        'PHP_AUTH_PW' => 'invalid-token-string',
    ])->getJson('/'.config('satis.routes.composer_prefix').'/p2/vendor/package.json');

    $response->assertUnauthorized();
});

it('skips license check on routes without middleware', function () {
    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_USER' => $token->email,
        'PHP_AUTH_PW' => $token->token,
    ])->getJson('/'.config('satis.routes.composer_prefix').'/packages.json');

    // packages.json has withoutMiddleware(EnsureUserHasLicense) so it should not return 401/403
    $response->assertNotFound(); // 404 because no build exists, but auth passed without license check
});
