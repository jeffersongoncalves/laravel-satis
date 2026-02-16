<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTenantPackages;

it('dispatches SyncTenantPackages job', function () {
    Bus::fake();

    $this->artisan('satis:build')
        ->expectsOutput('Dispatched Satis build job.')
        ->assertExitCode(0);

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('dispatches job with specific tenant id', function () {
    Bus::fake();

    $this->artisan('satis:build', ['--tenant' => 5])
        ->assertExitCode(0);

    Bus::assertDispatched(SyncTenantPackages::class, function ($job) {
        return true;
    });
});
