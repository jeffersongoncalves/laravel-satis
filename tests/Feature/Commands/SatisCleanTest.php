<?php

use Illuminate\Support\Facades\Storage;

it('cleans satis directory when it exists', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/tenant/packages.json', '{}');
    $disk->put($storagePath.'/token-1/p2/vendor/package.json', '{}');

    expect($disk->exists($storagePath))->toBeTrue();

    $this->artisan('satis:clean', ['--force' => true])
        ->expectsOutputToContain('Satis repositories cleaned successfully.')
        ->assertExitCode(0);

    expect($disk->exists($storagePath))->toBeFalse();
});

it('warns when no satis directory exists', function () {
    Storage::fake('local');

    $this->artisan('satis:clean', ['--force' => true])
        ->expectsOutputToContain('No Satis directory found.')
        ->assertExitCode(0);
});

it('cancels when user declines confirmation', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/test/packages.json', '{}');

    $this->artisan('satis:clean')
        ->expectsConfirmation('This will delete all Satis builds. Are you sure?', 'no')
        ->expectsOutputToContain('Operation cancelled.')
        ->assertExitCode(0);

    expect($disk->exists($storagePath.'/test/packages.json'))->toBeTrue();
});

it('proceeds when user confirms', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/test/packages.json', '{}');

    $this->artisan('satis:clean')
        ->expectsConfirmation('This will delete all Satis builds. Are you sure?', 'yes')
        ->expectsOutputToContain('Satis repositories cleaned successfully.')
        ->assertExitCode(0);

    expect($disk->exists($storagePath))->toBeFalse();
});
