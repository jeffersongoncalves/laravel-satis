<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->targetPath = base_path('satis.json');

    if (File::exists($this->targetPath)) {
        File::delete($this->targetPath);
    }
});

afterEach(function () {
    if (File::exists($this->targetPath)) {
        File::delete($this->targetPath);
    }
});

it('publishes satis.json with interactive prompts', function () {
    $this->artisan('satis:install')
        ->expectsQuestion('What is the repository name?', 'my-company/packages')
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    expect(File::exists($this->targetPath))->toBeTrue();

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'my-company/packages')
        ->not->toHaveKey('homepage');
});

it('publishes satis.json with command options', function () {
    $this->artisan('satis:install', [
        '--name' => 'acme/repo',
    ])
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    expect(File::exists($this->targetPath))->toBeTrue();

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'acme/repo')
        ->not->toHaveKey('homepage');
});

it('asks for confirmation when satis.json already exists', function () {
    File::put($this->targetPath, '{}');

    $this->artisan('satis:install')
        ->expectsConfirmation('A satis.json file already exists. Do you want to overwrite it?', 'no')
        ->expectsOutput('Installation cancelled.')
        ->assertExitCode(0);

    expect(File::get($this->targetPath))->toBe('{}');
});

it('overwrites existing satis.json when confirmed', function () {
    File::put($this->targetPath, '{}');

    $this->artisan('satis:install')
        ->expectsConfirmation('A satis.json file already exists. Do you want to overwrite it?', 'yes')
        ->expectsQuestion('What is the repository name?', 'new/repo')
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'new/repo');
});

it('overwrites existing satis.json with force option', function () {
    File::put($this->targetPath, '{}');

    $this->artisan('satis:install', [
        '--name' => 'forced/repo',
        '--force' => true,
    ])
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'forced/repo');
});

it('preserves default satis.json structure', function () {
    $this->artisan('satis:install', [
        '--name' => 'test/repo',
    ])->assertExitCode(0);

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'test/repo')
        ->not->toHaveKey('homepage')
        ->toHaveKey('repositories')
        ->toHaveKey('require-all', false)
        ->toHaveKey('output-html', false)
        ->toHaveKey('archive')
        ->toHaveKey('minimum-stability', 'stable');

    expect($content['archive'])
        ->toHaveKey('directory', 'archives')
        ->toHaveKey('skip-dev', true);
});
