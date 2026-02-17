<?php

use Illuminate\Support\Facades\Storage;

it('sanitizes satis directories', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $jsonWithCredentials = json_encode([
        'packages' => [
            'vendor/package' => [
                [
                    'name' => 'vendor/package',
                    'version' => '1.0.0',
                    'transport-options' => ['ssl' => ['verify_peer' => false]],
                ],
            ],
        ],
    ]);

    $disk->put($storagePath.'/tenant/p2/vendor/package.json', $jsonWithCredentials);
    $disk->put($storagePath.'/token-1/p2/vendor/package.json', $jsonWithCredentials);

    $this->artisan('satis:sanitize')
        ->expectsOutputToContain('Sanitized 2 Satis directories.')
        ->assertExitCode(0);

    $content1 = json_decode($disk->get($storagePath.'/tenant/p2/vendor/package.json'), true);
    $content2 = json_decode($disk->get($storagePath.'/token-1/p2/vendor/package.json'), true);

    expect($content1['packages']['vendor/package'][0])->not->toHaveKey('transport-options');
    expect($content2['packages']['vendor/package'][0])->not->toHaveKey('transport-options');
});

it('warns when no satis directory exists', function () {
    Storage::fake('local');

    $this->artisan('satis:sanitize')
        ->expectsOutputToContain('No Satis directory found.')
        ->assertExitCode(0);
});

it('handles empty satis directory', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');
    $storagePath = config('satis.storage_path', 'satis');

    $disk->put($storagePath.'/.gitkeep', '');

    $this->artisan('satis:sanitize')
        ->expectsOutputToContain('Sanitized 0 Satis directories.')
        ->assertExitCode(0);
});
