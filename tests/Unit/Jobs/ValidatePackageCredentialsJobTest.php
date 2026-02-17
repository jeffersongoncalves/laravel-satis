<?php

use JeffersonGoncalves\LaravelSatis\Actions\ValidatePackageCredentials;
use JeffersonGoncalves\LaravelSatis\Jobs\ValidatePackageCredentialsJob;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('calls ValidatePackageCredentials action', function () {
    $package = Package::factory()->create();

    $action = Mockery::mock(ValidatePackageCredentials::class);
    $action->shouldReceive('execute')->once()->with($package)->andReturnTrue();

    app()->instance(ValidatePackageCredentials::class, $action);

    $job = new ValidatePackageCredentialsJob($package);
    $job->handle(app(ValidatePackageCredentials::class));
});
