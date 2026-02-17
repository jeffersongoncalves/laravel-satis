<?php

namespace JeffersonGoncalves\LaravelSatis\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\SanitizeSatisPackages;

class SatisSanitize extends Command
{
    protected $signature = 'satis:sanitize';

    protected $description = 'Remove credentials from Satis JSON files';

    public function handle(SanitizeSatisPackages $sanitizer): int
    {
        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        if (! $disk->exists($storagePath)) {
            $this->components->warn('No Satis directory found.');

            return self::SUCCESS;
        }

        $directories = $disk->directories($storagePath);
        $count = 0;

        foreach ($directories as $directory) {
            $sanitizer->sanitizeDirectory($directory, $disk);
            $count++;
        }

        $this->components->info("Sanitized {$count} Satis directories.");

        return self::SUCCESS;
    }
}
