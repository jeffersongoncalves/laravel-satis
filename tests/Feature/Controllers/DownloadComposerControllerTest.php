<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\DownloadComposerJob;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('dispatches download jobs for valid packages', function () {
    Bus::fake([DownloadComposerJob::class]);

    $package = Package::factory()->create(['name' => 'vendor/my-package']);
    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_PW' => $token->token,
    ])->postJson(
        '/'.config('satis.routes.api_prefix').'/composer/downloads',
        [
            'downloads' => [
                ['name' => 'vendor/my-package', 'version' => '1.0.0'],
            ],
        ]
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertDispatched(DownloadComposerJob::class);
});

it('returns ok for empty downloads', function () {
    Bus::fake([DownloadComposerJob::class]);

    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_PW' => $token->token,
    ])->postJson(
        '/'.config('satis.routes.api_prefix').'/composer/downloads',
        ['downloads' => []]
    );

    $response->assertOk()
        ->assertJson(['status' => 'ok']);

    Bus::assertNotDispatched(DownloadComposerJob::class);
});

it('skips downloads with missing name or version', function () {
    Bus::fake([DownloadComposerJob::class]);

    $token = Token::factory()->create();

    $response = $this->withHeaders([
        'PHP_AUTH_PW' => $token->token,
    ])->postJson(
        '/'.config('satis.routes.api_prefix').'/composer/downloads',
        [
            'downloads' => [
                ['name' => null, 'version' => '1.0.0'],
                ['name' => 'vendor/pkg', 'version' => null],
            ],
        ]
    );

    $response->assertOk();
    Bus::assertNotDispatched(DownloadComposerJob::class);
});
