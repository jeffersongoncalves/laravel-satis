<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTenantPackages;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('accepts valid github webhook', function () {
    Bus::fake();

    $package = Package::factory()->create();

    $response = $this->postJson(
        '/'.config('laravel-satis.routes.api_prefix').'/webhooks/github/'.$package->reference,
        ['ref' => 'refs/heads/main']
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('validates webhook signature when secret is set', function () {
    Bus::fake();

    $package = Package::factory()->create();
    $payload = json_encode(['ref' => 'refs/heads/main']);
    $signature = 'sha256='.hash_hmac('sha256', $payload, $package->webhook_secret);

    $response = $this->call('POST',
        '/'.config('laravel-satis.routes.api_prefix').'/webhooks/github/'.$package->reference,
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ],
        $payload
    );

    expect($response->status())->toBe(200);
});

it('rejects invalid webhook signature', function () {
    Bus::fake();

    $package = Package::factory()->create();
    $payload = json_encode(['ref' => 'refs/heads/main']);

    $response = $this->call('POST',
        '/'.config('laravel-satis.routes.api_prefix').'/webhooks/github/'.$package->reference,
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid-signature',
        ],
        $payload
    );

    expect($response->status())->toBe(403);
});

it('returns 404 for non-existent package reference', function () {
    Bus::fake();

    $response = $this->postJson(
        config('laravel-satis.routes.api_prefix').'/webhooks/github/non-existent-ref',
        ['ref' => 'refs/heads/main']
    );

    $response->assertNotFound();
});
