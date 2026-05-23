<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\ValidatePackageCredentials;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTenantPackages;
use JeffersonGoncalves\LaravelSatis\Jobs\ValidateTenantSatisBuild;
use JeffersonGoncalves\LaravelSatis\Jobs\ValidateTokenSatisBuild;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('dispatches SyncTenantPackages when packages.json does not exist', function () {
    Storage::fake('local');

    Package::factory()->create();

    Bus::fake([SyncTenantPackages::class, ValidateTokenSatisBuild::class]);

    $job = new ValidateTenantSatisBuild;
    $job->handle(app(ValidatePackageCredentials::class));

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('dispatches SyncTenantPackages when package was updated after last build', function () {
    Storage::fake('local');

    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/tenant/packages.json', '{"packages":{}}');

    $package = Package::factory()->create();

    // Simulate build being older than package update
    touch($disk->path($storagePath.'/tenant/packages.json'), now()->subHour()->timestamp);

    $package->update(['name' => 'vendor/updated-package']);

    Bus::fake([SyncTenantPackages::class, ValidateTokenSatisBuild::class]);

    $job = new ValidateTenantSatisBuild;
    $job->handle(app(ValidatePackageCredentials::class));

    Bus::assertDispatched(SyncTenantPackages::class);
});

it('does not dispatch SyncTenantPackages when build is up to date', function () {
    Storage::fake('local');

    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $package = Package::factory()->create();

    // Create packages.json AFTER the package, making the build "newer"
    $disk->put($storagePath.'/tenant/packages.json', '{"packages":{}}');
    touch($disk->path($storagePath.'/tenant/packages.json'), $package->updated_at->addMinute()->timestamp);

    Bus::fake([SyncTenantPackages::class, ValidateTokenSatisBuild::class]);

    $job = new ValidateTenantSatisBuild;
    $job->handle(app(ValidatePackageCredentials::class));

    Bus::assertNotDispatched(SyncTenantPackages::class);
});

it('dispatches ValidateTokenSatisBuild for each token', function () {
    Storage::fake('local');

    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    Package::factory()->create();
    Token::factory()->count(2)->create();

    $disk->put($storagePath.'/tenant/packages.json', '{"packages":{}}');

    Bus::fake([SyncTenantPackages::class, ValidateTokenSatisBuild::class]);

    $job = new ValidateTenantSatisBuild;
    $job->handle(app(ValidatePackageCredentials::class));

    Bus::assertDispatched(ValidateTokenSatisBuild::class, 2);
});
