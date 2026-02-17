<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class SanitizeSatisPackages
{
    /**
     * Sanitize console output by removing ANSI color codes.
     */
    public function execute(string $output): string
    {
        $lines = explode("\n", $output);
        $sanitized = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                continue;
            }

            $trimmed = preg_replace('/\e\[[0-9;]*m/', '', $trimmed);

            $sanitized[] = $trimmed;
        }

        return implode("\n", $sanitized);
    }

    /**
     * Sanitize all JSON files in the given Satis output directory,
     * removing transport-options that may contain credentials.
     */
    public function sanitizeDirectory(string $buildPath, ?Filesystem $disk = null): void
    {
        $disk = $disk ?? Storage::disk(config('satis.storage_disk'));

        $directories = [
            rtrim($buildPath, '/').'/p2',
            rtrim($buildPath, '/').'/include',
        ];

        foreach ($directories as $dir) {
            if (! $disk->exists($dir)) {
                continue;
            }

            $files = $disk->allFiles($dir);

            foreach ($files as $file) {
                if (str_ends_with($file, '.json')) {
                    $this->sanitizeJsonFile($file, $disk);
                }
            }
        }
    }

    /**
     * Remove transport-options from a single JSON file.
     */
    protected function sanitizeJsonFile(string $filePath, Filesystem $disk): void
    {
        $content = $disk->get($filePath);

        $data = json_decode($content, true);

        if ($data === null) {
            return;
        }

        $modified = false;

        if (isset($data['packages']) && is_array($data['packages'])) {
            foreach ($data['packages'] as $packageName => $versions) {
                if (! is_array($versions)) {
                    continue;
                }

                foreach ($versions as $index => $versionData) {
                    if (isset($versionData['transport-options'])) {
                        unset($data['packages'][$packageName][$index]['transport-options']);
                        $modified = true;
                    }
                }
            }
        }

        if ($modified) {
            $disk->put(
                $filePath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }
    }
}
