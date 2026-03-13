<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\MergeSatisPackagesJson;

it('merges multiple snapshots into one packages.json', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');

    $snapshot1 = [
        'packages' => [
            'vendor/package-a' => [
                ['version' => '1.0.0', 'name' => 'vendor/package-a'],
            ],
        ],
        'available-packages' => ['vendor/package-a'],
    ];

    $snapshot2 = [
        'packages' => [
            'vendor/package-b' => [
                ['version' => '2.0.0', 'name' => 'vendor/package-b'],
            ],
        ],
        'available-packages' => ['vendor/package-b'],
    ];

    $disk->put('output/snapshot_0/packages.json', json_encode($snapshot1));
    $disk->put('output/snapshot_1/packages.json', json_encode($snapshot2));

    $action = new MergeSatisPackagesJson;
    $action->handle('output', [
        'output/snapshot_0/packages.json',
        'output/snapshot_1/packages.json',
    ], $disk);

    expect($disk->exists('output/packages.json'))->toBeTrue();

    $merged = json_decode($disk->get('output/packages.json'), true);

    expect($merged['packages'])->toHaveKey('vendor/package-a')
        ->toHaveKey('vendor/package-b')
        ->and($merged['available-packages'])->toContain('vendor/package-a')
        ->toContain('vendor/package-b');
});

it('handles single snapshot without merging', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');

    $snapshot = [
        'packages' => [
            'vendor/package-a' => [
                ['version' => '1.0.0'],
            ],
        ],
    ];

    $disk->put('output/snapshot_0/packages.json', json_encode($snapshot));

    $action = new MergeSatisPackagesJson;
    $action->handle('output', [
        'output/snapshot_0/packages.json',
    ], $disk);

    expect($disk->exists('output/packages.json'))->toBeTrue();

    $result = json_decode($disk->get('output/packages.json'), true);
    expect($result['packages'])->toHaveKey('vendor/package-a');
});

it('handles empty snapshots array', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');

    $action = new MergeSatisPackagesJson;
    $action->handle('output', [], $disk);

    expect($disk->exists('output/packages.json'))->toBeFalse();
});

it('deduplicates available-packages', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');

    $snapshot1 = [
        'packages' => [],
        'available-packages' => ['vendor/pkg-a', 'vendor/pkg-b'],
    ];

    $snapshot2 = [
        'packages' => [],
        'available-packages' => ['vendor/pkg-b', 'vendor/pkg-c'],
    ];

    $disk->put('output/snapshot_0/packages.json', json_encode($snapshot1));
    $disk->put('output/snapshot_1/packages.json', json_encode($snapshot2));

    $action = new MergeSatisPackagesJson;
    $action->handle('output', [
        'output/snapshot_0/packages.json',
        'output/snapshot_1/packages.json',
    ], $disk);

    $merged = json_decode($disk->get('output/packages.json'), true);

    expect($merged['available-packages'])->toHaveCount(3)
        ->toContain('vendor/pkg-a')
        ->toContain('vendor/pkg-b')
        ->toContain('vendor/pkg-c');
});

it('skips non-existent snapshot files', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');

    $snapshot1 = [
        'packages' => [
            'vendor/package-a' => [['version' => '1.0.0']],
        ],
    ];

    $disk->put('output/snapshot_0/packages.json', json_encode($snapshot1));

    $action = new MergeSatisPackagesJson;
    $action->handle('output', [
        'output/snapshot_0/packages.json',
        'output/nonexistent/packages.json',
    ], $disk);

    expect($disk->exists('output/packages.json'))->toBeTrue();

    $merged = json_decode($disk->get('output/packages.json'), true);
    expect($merged['packages'])->toHaveKey('vendor/package-a');
});
