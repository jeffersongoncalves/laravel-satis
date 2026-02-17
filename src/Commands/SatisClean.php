<?php

namespace JeffersonGoncalves\LaravelSatis\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SatisClean extends Command
{
    protected $signature = 'satis:clean {--force : Force clean without confirmation}';

    protected $description = 'Clean all Satis repository builds from storage';

    public function handle(): int
    {
        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        if (! $disk->exists($storagePath)) {
            $this->components->warn('No Satis directory found.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->components->confirm('This will delete all Satis builds. Are you sure?')) {
            $this->components->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $disk->deleteDirectory($storagePath);

        $this->components->info('Satis repositories cleaned successfully.');

        return self::SUCCESS;
    }
}
