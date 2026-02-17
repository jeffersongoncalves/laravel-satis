<?php

namespace JeffersonGoncalves\LaravelSatis\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SatisInstall extends Command
{
    protected $signature = 'satis:install
        {--name= : The repository name (e.g. my-company/repository)}
        {--homepage= : The homepage URL for the Satis repository}
        {--force : Overwrite existing satis.json file}';

    protected $description = 'Install and configure the Satis repository by publishing the satis.json to the project root';

    public function handle(): int
    {
        $targetPath = base_path('satis.json');

        if (File::exists($targetPath) && ! $this->option('force')) {
            if (! $this->confirm('A satis.json file already exists. Do you want to overwrite it?', false)) {
                $this->info('Installation cancelled.');

                return self::SUCCESS;
            }
        }

        $name = $this->option('name') ?? $this->ask(
            'What is the repository name?',
            config('laravel-satis.satis.name', 'my/repository')
        );

        $homepage = $this->option('homepage') ?? $this->ask(
            'What is the homepage URL for the Satis repository?',
            url(config('laravel-satis.routes.composer_prefix', 'satis'))
        );

        $stubPath = __DIR__.'/../../stubs/satis.json.stub';

        if (! File::exists($stubPath)) {
            $this->error('Stub file not found at: '.$stubPath);

            return self::FAILURE;
        }

        $content = File::get($stubPath);
        $content = str_replace('{{ name }}', $name, $content);
        $content = str_replace('{{ homepage }}', $homepage, $content);

        File::put($targetPath, $content);

        $this->info('satis.json has been published to the project root.');
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Name</>', $name);
        $this->components->twoColumnDetail('<fg=green>Homepage</>', $homepage);
        $this->newLine();
        $this->info('You can now customize the satis.json file as needed.');

        return self::SUCCESS;
    }
}
