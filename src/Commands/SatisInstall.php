<?php

namespace JeffersonGoncalves\LaravelSatis\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SatisInstall extends Command
{
    protected $signature = 'satis:install
        {--name= : The repository name (e.g. my-company/repository)}
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

        $stubPath = __DIR__.'/../../stubs/satis.json.stub';

        if (! File::exists($stubPath)) {
            $this->error('Stub file not found at: '.$stubPath);

            return self::FAILURE;
        }

        $content = File::get($stubPath);
        $content = str_replace('{{ name }}', $name, $content);

        File::put($targetPath, $content);

        $this->info('satis.json has been published to the project root.');
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Name</>', $name);
        $this->newLine();
        $this->info('You can now customize the satis.json file as needed.');

        return self::SUCCESS;
    }
}
