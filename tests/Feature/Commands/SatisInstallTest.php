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
        ->expectsQuestion('What is the homepage URL for the Satis repository?', 'https://packages.example.com')
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    expect(File::exists($this->targetPath))->toBeTrue();

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'my-company/packages')
        ->toHaveKey('homepage', 'https://packages.example.com');
});

it('publishes satis.json with command options', function () {
    $this->artisan('satis:install', [
        '--name' => 'acme/repo',
        '--homepage' => 'https://repo.acme.com',
    ])
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    expect(File::exists($this->targetPath))->toBeTrue();

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'acme/repo')
        ->toHaveKey('homepage', 'https://repo.acme.com');
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
        ->expectsQuestion('What is the homepage URL for the Satis repository?', 'https://new.example.com')
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'new/repo')
        ->toHaveKey('homepage', 'https://new.example.com');
});

it('overwrites existing satis.json with force option', function () {
    File::put($this->targetPath, '{}');

    $this->artisan('satis:install', [
        '--name' => 'forced/repo',
        '--homepage' => 'https://forced.example.com',
        '--force' => true,
    ])
        ->expectsOutput('satis.json has been published to the project root.')
        ->assertExitCode(0);

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'forced/repo')
        ->toHaveKey('homepage', 'https://forced.example.com');
});

it('preserves default satis.json structure', function () {
    $this->artisan('satis:install', [
        '--name' => 'test/repo',
        '--homepage' => 'https://test.example.com',
    ])->assertExitCode(0);

    $content = json_decode(File::get($this->targetPath), true);

    expect($content)
        ->toHaveKey('name', 'test/repo')
        ->toHaveKey('homepage', 'https://test.example.com')
        ->toHaveKey('repositories')
        ->toHaveKey('require-all', false)
        ->toHaveKey('output-html', false)
        ->toHaveKey('archive')
        ->toHaveKey('minimum-stability', 'stable');

    expect($content['archive'])
        ->toHaveKey('directory', 'archives')
        ->toHaveKey('skip-dev', true);
});
