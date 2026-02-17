<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTenantPackages;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

function webhookUrl(Package $package): string
{
    return '/'.config('satis.routes.api_prefix').'/webhooks/github/'.$package->reference;
}

it('accepts valid github webhook with push event', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();

    $response = $this->postJson(
        webhookUrl($package),
        ['ref' => 'refs/heads/main'],
        ['X-GitHub-Event' => 'push']
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('accepts valid github webhook with release event', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();

    $response = $this->postJson(
        webhookUrl($package),
        ['action' => 'published'],
        ['X-GitHub-Event' => 'release']
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('accepts valid github webhook with create event', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();

    $response = $this->postJson(
        webhookUrl($package),
        ['ref_type' => 'tag'],
        ['X-GitHub-Event' => 'create']
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('ignores unsupported github events', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();

    $response = $this->postJson(
        webhookUrl($package),
        ['action' => 'opened'],
        ['X-GitHub-Event' => 'pull_request']
    );

    $response->assertOk()
        ->assertJson(['message' => 'Event ignored']);

    Bus::assertNotDispatched(SyncTenantPackages::class);
});

it('ignores webhook without event header', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();

    $response = $this->postJson(
        webhookUrl($package),
        ['ref' => 'refs/heads/main']
    );

    $response->assertOk()
        ->assertJson(['message' => 'Event ignored']);

    Bus::assertNotDispatched(SyncTenantPackages::class);
});

it('rejects webhook for non-github package', function () {
    Bus::fake();

    $package = Package::factory()->create(['type' => PackageType::Composer]);

    $response = $this->postJson(
        webhookUrl($package),
        ['ref' => 'refs/heads/main'],
        ['X-GitHub-Event' => 'push']
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Package is not a GitHub package']);

    Bus::assertNotDispatched(SyncTenantPackages::class);
});

it('validates webhook signature when secret is set', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();
    $payload = json_encode(['ref' => 'refs/heads/main']);
    $signature = 'sha256='.hash_hmac('sha256', $payload, $package->webhook_secret);

    $response = $this->call('POST',
        webhookUrl($package),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
            'HTTP_X_GITHUB_EVENT' => 'push',
        ],
        $payload
    );

    expect($response->status())->toBe(200);

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('rejects invalid webhook signature', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();
    $payload = json_encode(['ref' => 'refs/heads/main']);

    $response = $this->call('POST',
        webhookUrl($package),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid-signature',
            'HTTP_X_GITHUB_EVENT' => 'push',
        ],
        $payload
    );

    expect($response->status())->toBe(403);

    Bus::assertNotDispatched(SyncTenantPackages::class);
});

it('returns 404 for non-existent package reference', function () {
    Bus::fake();

    $response = $this->postJson(
        '/'.config('satis.routes.api_prefix').'/webhooks/github/non-existent-ref',
        ['ref' => 'refs/heads/main'],
        ['X-GitHub-Event' => 'push']
    );

    $response->assertNotFound();
});

it('dispatches SyncTokenPackages for each token of the package', function () {
    $package = Package::factory()->github()->create();
    $token1 = Token::factory()->create();
    $token2 = Token::factory()->create();

    $package->tokens()->attach([$token1->id, $token2->id]);

    Bus::fake();

    $response = $this->postJson(
        webhookUrl($package),
        ['ref' => 'refs/heads/main'],
        ['X-GitHub-Event' => 'push']
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertDispatched(SyncTenantPackages::class);
    Bus::assertDispatched(SyncTokenPackages::class, 2);
});

it('does not dispatch SyncTokenPackages when package has no tokens', function () {
    Bus::fake();

    $package = Package::factory()->github()->create();

    $response = $this->postJson(
        webhookUrl($package),
        ['ref' => 'refs/heads/main'],
        ['X-GitHub-Event' => 'push']
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertDispatched(SyncTenantPackages::class);
    Bus::assertNotDispatched(SyncTokenPackages::class);
});
