<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Jobs\ValidateTokenSatisBuild;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;

it('dispatches SyncTokenPackages when packages.json does not exist', function () {
    Storage::fake('local');

    $token = Token::factory()->create();

    Bus::fake([SyncTokenPackages::class]);

    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    // Tenant build must exist
    $disk->put($storagePath.'/tenant/packages.json', '{"packages":{}}');

    $job = new ValidateTokenSatisBuild($token);
    $job->handle();

    Bus::assertDispatched(SyncTokenPackages::class);
});

it('dispatches SyncTokenPackages when packages.json is empty', function () {
    Storage::fake('local');

    $token = Token::factory()->create();

    Bus::fake([SyncTokenPackages::class]);

    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/tenant/packages.json', '{"packages":{}}');
    $disk->put($storagePath.'/'.$token->id.'/packages.json', '{}');

    $job = new ValidateTokenSatisBuild($token);
    $job->handle();

    Bus::assertDispatched(SyncTokenPackages::class);
});

it('dispatches SyncTokenPackages when pivot was updated after last build', function () {
    Storage::fake('local');

    $token = Token::factory()->create();
    $package = Package::factory()->create();

    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/tenant/packages.json', '{"packages":{}}');
    $disk->put($storagePath.'/'.$token->id.'/packages.json', '{"packages":{"vendor/pkg":[]}}');

    // Build is old
    touch($disk->path($storagePath.'/'.$token->id.'/packages.json'), now()->subHour()->timestamp);

    // Attach token to package (creates pivot with current timestamp)
    $token->packages()->attach($package->id);

    Bus::fake([SyncTokenPackages::class]);

    $job = new ValidateTokenSatisBuild($token);
    $job->handle();

    Bus::assertDispatched(SyncTokenPackages::class);
});

it('does not dispatch SyncTokenPackages when build is up to date', function () {
    Storage::fake('local');

    $token = Token::factory()->create();
    $package = Package::factory()->create();
    $token->packages()->attach($package->id);

    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/tenant/packages.json', '{"packages":{}}');

    // Build is recent (after the pivot was created)
    sleep(1);
    $disk->put($storagePath.'/'.$token->id.'/packages.json', '{"packages":{"vendor/pkg":[]}}');

    Bus::fake([SyncTokenPackages::class]);

    $job = new ValidateTokenSatisBuild($token);
    $job->handle();

    Bus::assertNotDispatched(SyncTokenPackages::class);
});

it('skips validation when tenant build does not exist', function () {
    Storage::fake('local');

    $token = Token::factory()->create();

    Bus::fake([SyncTokenPackages::class]);

    $job = new ValidateTokenSatisBuild($token);
    $job->handle();

    Bus::assertNotDispatched(SyncTokenPackages::class);
});
