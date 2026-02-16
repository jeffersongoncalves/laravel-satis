<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\ValidateTenantSatisBuild;

it('dispatches ValidateTenantSatisBuild job', function () {
    Bus::fake();

    $this->artisan('satis:validate')
        ->expectsOutput('Dispatched Satis validation job.')
        ->assertExitCode(0);

    Bus::assertDispatched(ValidateTenantSatisBuild::class);
});

it('dispatches job with specific tenant id', function () {
    Bus::fake();

    $this->artisan('satis:validate', ['--tenant' => 3])
        ->assertExitCode(0);

    Bus::assertDispatched(ValidateTenantSatisBuild::class);
});
