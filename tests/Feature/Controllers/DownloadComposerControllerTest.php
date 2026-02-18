<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\DownloadComposerJob;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('dispatches download jobs for valid packages', function () {
    Bus::fake([DownloadComposerJob::class]);

    Package::factory()->create(['name' => 'vendor/my-package']);

    $response = $this->postJson(
        '/'.config('satis.routes.api_prefix').'/composer/downloads',
        [
            'downloads' => [
                ['name' => 'vendor/my-package', 'version' => '1.0.0'],
            ],
        ]
    );

    $response->assertStatus(201);

    Bus::assertDispatched(DownloadComposerJob::class);
});

it('returns 201 for empty downloads', function () {
    Bus::fake([DownloadComposerJob::class]);

    $response = $this->postJson(
        '/'.config('satis.routes.api_prefix').'/composer/downloads',
        ['downloads' => []]
    );

    $response->assertStatus(201);

    Bus::assertNotDispatched(DownloadComposerJob::class);
});

it('skips downloads for unknown packages', function () {
    Bus::fake([DownloadComposerJob::class]);

    $response = $this->postJson(
        '/'.config('satis.routes.api_prefix').'/composer/downloads',
        [
            'downloads' => [
                ['name' => 'vendor/nonexistent', 'version' => '1.0.0'],
            ],
        ]
    );

    $response->assertStatus(201);
    Bus::assertNotDispatched(DownloadComposerJob::class);
});
