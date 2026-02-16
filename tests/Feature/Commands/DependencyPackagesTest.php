<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\ProcessPackageDependency;

it('dispatches ProcessPackageDependency job', function () {
    Bus::fake();

    $this->artisan('dependency:packages')
        ->expectsOutput('Dispatched dependency processing job.')
        ->assertExitCode(0);

    Bus::assertDispatched(ProcessPackageDependency::class);
});
