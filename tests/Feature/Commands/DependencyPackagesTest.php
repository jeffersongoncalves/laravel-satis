<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\LaravelSatis\Jobs\ProcessPackageDependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('dispatches ProcessPackageDependency job for each package', function () {
    Bus::fake();

    $packages = Package::factory()->count(3)->create();

    $this->artisan('dependency:packages')
        ->assertExitCode(0);

    Bus::assertDispatchedTimes(ProcessPackageDependency::class, 3);
});

it('does not dispatch when no packages exist', function () {
    Bus::fake();

    $this->artisan('dependency:packages')
        ->expectsOutput('Dispatched dependency processing for 0 packages.')
        ->assertExitCode(0);

    Bus::assertNotDispatched(ProcessPackageDependency::class);
});
