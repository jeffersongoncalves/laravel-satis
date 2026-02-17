<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\SanitizeSatisPackages;

it('removes ANSI color codes', function () {
    $action = new SanitizeSatisPackages;
    $output = "\e[32mSuccess\e[0m: Package built";

    $result = $action->execute($output);

    expect($result)->toBe('Success: Package built');
});

it('removes empty lines', function () {
    $action = new SanitizeSatisPackages;
    $output = "Line 1\n\n\nLine 2\n\nLine 3";

    $result = $action->execute($output);

    expect($result)->toBe("Line 1\nLine 2\nLine 3");
});

it('trims whitespace from lines', function () {
    $action = new SanitizeSatisPackages;
    $output = "  Line 1  \n  Line 2  ";

    $result = $action->execute($output);

    expect($result)->toBe("Line 1\nLine 2");
});

it('handles empty output', function () {
    $action = new SanitizeSatisPackages;

    $result = $action->execute('');

    expect($result)->toBe('');
});

it('handles multiple ANSI codes in one line', function () {
    $action = new SanitizeSatisPackages;
    $output = "\e[1;33mWarning\e[0m: \e[31mError\e[0m occurred";

    $result = $action->execute($output);

    expect($result)->toBe('Warning: Error occurred');
});

it('removes transport-options from p2 json files', function () {
    $disk = Storage::fake('local');
    $buildPath = 'satis/test-build';

    $jsonContent = json_encode([
        'packages' => [
            'vendor/package' => [
                [
                    'name' => 'vendor/package',
                    'version' => '1.0.0',
                    'transport-options' => [
                        'ssl' => ['verify_peer' => false],
                        'http' => ['header' => ['Authorization: Basic dXNlcjpwYXNz']],
                    ],
                ],
                [
                    'name' => 'vendor/package',
                    'version' => '2.0.0',
                ],
            ],
        ],
    ], JSON_PRETTY_PRINT);

    $disk->put($buildPath.'/p2/vendor/package.json', $jsonContent);

    $action = new SanitizeSatisPackages;
    $action->sanitizeDirectory($buildPath, $disk);

    $result = json_decode($disk->get($buildPath.'/p2/vendor/package.json'), true);

    expect($result['packages']['vendor/package'][0])->not->toHaveKey('transport-options')
        ->and($result['packages']['vendor/package'][0]['name'])->toBe('vendor/package')
        ->and($result['packages']['vendor/package'][1])->not->toHaveKey('transport-options');
});

it('removes transport-options from include json files', function () {
    $disk = Storage::fake('local');
    $buildPath = 'satis/test-build';

    $jsonContent = json_encode([
        'packages' => [
            'vendor/other' => [
                [
                    'name' => 'vendor/other',
                    'version' => '3.0.0',
                    'transport-options' => ['http' => ['header' => ['Authorization: Basic abc123']]],
                ],
            ],
        ],
    ], JSON_PRETTY_PRINT);

    $disk->put($buildPath.'/include/all$abc123.json', $jsonContent);

    $action = new SanitizeSatisPackages;
    $action->sanitizeDirectory($buildPath, $disk);

    $result = json_decode($disk->get($buildPath.'/include/all$abc123.json'), true);

    expect($result['packages']['vendor/other'][0])->not->toHaveKey('transport-options')
        ->and($result['packages']['vendor/other'][0]['name'])->toBe('vendor/other');
});

it('does not modify json files without transport-options', function () {
    $disk = Storage::fake('local');
    $buildPath = 'satis/test-build';

    $jsonContent = json_encode([
        'packages' => [
            'vendor/clean' => [
                ['name' => 'vendor/clean', 'version' => '1.0.0'],
            ],
        ],
    ], JSON_PRETTY_PRINT);

    $disk->put($buildPath.'/p2/vendor/clean.json', $jsonContent);

    $originalContent = $disk->get($buildPath.'/p2/vendor/clean.json');

    $action = new SanitizeSatisPackages;
    $action->sanitizeDirectory($buildPath, $disk);

    expect($disk->get($buildPath.'/p2/vendor/clean.json'))->toBe($originalContent);
});

it('skips non-existent directories gracefully', function () {
    $disk = Storage::fake('local');
    $buildPath = 'satis/non-existent';

    $action = new SanitizeSatisPackages;
    $action->sanitizeDirectory($buildPath, $disk);

    expect(true)->toBeTrue();
});

it('skips invalid json files', function () {
    $disk = Storage::fake('local');
    $buildPath = 'satis/test-build';

    $disk->put($buildPath.'/p2/invalid.json', 'not valid json {{{');

    $action = new SanitizeSatisPackages;
    $action->sanitizeDirectory($buildPath, $disk);

    expect($disk->get($buildPath.'/p2/invalid.json'))->toBe('not valid json {{{');
});
