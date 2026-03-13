<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class MergeSatisPackagesJson
{
    /**
     * Merge multiple satis packages.json snapshots into a unified output.
     *
     * @param  string  $outputDir  The final output directory
     * @param  array<string>  $snapshots  Paths to snapshot packages.json files
     */
    public function handle(string $outputDir, array $snapshots, ?Filesystem $disk = null): void
    {
        $disk = $disk ?? Storage::disk(config('satis.storage_disk'));

        if (empty($snapshots)) {
            return;
        }

        if (count($snapshots) === 1) {
            $this->copySingleSnapshot($disk, $snapshots[0], $outputDir);

            return;
        }

        $mergedPackages = [];
        $mergedIncludes = [];
        $mergedAvailablePackages = [];
        $baseConfig = null;

        foreach ($snapshots as $snapshotPath) {
            if (! $disk->exists($snapshotPath)) {
                continue;
            }

            $content = json_decode($disk->get($snapshotPath), true);

            if ($content === null) {
                continue;
            }

            if ($baseConfig === null) {
                $baseConfig = $content;
            }

            if (isset($content['packages']) && is_array($content['packages'])) {
                foreach ($content['packages'] as $packageName => $versions) {
                    if (! isset($mergedPackages[$packageName])) {
                        $mergedPackages[$packageName] = [];
                    }
                    $mergedPackages[$packageName] = array_merge(
                        $mergedPackages[$packageName],
                        is_array($versions) ? $versions : []
                    );
                }
            }

            if (isset($content['includes']) && is_array($content['includes'])) {
                foreach ($content['includes'] as $includeFile => $meta) {
                    $mergedIncludes[$includeFile] = $meta;
                }
            }

            if (isset($content['available-packages']) && is_array($content['available-packages'])) {
                $mergedAvailablePackages = array_merge(
                    $mergedAvailablePackages,
                    $content['available-packages']
                );
            }

            $snapshotDir = dirname($snapshotPath);
            $this->copySnapshotFiles($disk, $snapshotDir, $outputDir);
        }

        if ($baseConfig === null) {
            return;
        }

        $merged = $baseConfig;

        if (! empty($mergedPackages)) {
            $merged['packages'] = $mergedPackages;
        }

        if (! empty($mergedIncludes)) {
            $merged['includes'] = $this->recalculateIncludes($disk, $mergedIncludes, $outputDir);
        }

        if (! empty($mergedAvailablePackages)) {
            $mergedAvailablePackages = array_unique($mergedAvailablePackages);
            sort($mergedAvailablePackages);
            $merged['available-packages'] = $mergedAvailablePackages;
        }

        $disk->put(
            rtrim($outputDir, '/').'/packages.json',
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        foreach ($snapshots as $snapshotPath) {
            $snapshotDir = dirname($snapshotPath);
            if ($snapshotDir !== $outputDir && $disk->exists($snapshotDir)) {
                $disk->deleteDirectory($snapshotDir);
            }
        }
    }

    protected function copySingleSnapshot(Filesystem $disk, string $snapshotPath, string $outputDir): void
    {
        if (! $disk->exists($snapshotPath)) {
            return;
        }

        $snapshotDir = dirname($snapshotPath);

        if ($snapshotDir === $outputDir) {
            return;
        }

        $this->copySnapshotFiles($disk, $snapshotDir, $outputDir);

        $disk->put(
            rtrim($outputDir, '/').'/packages.json',
            $disk->get($snapshotPath)
        );

        $disk->deleteDirectory($snapshotDir);
    }

    protected function copySnapshotFiles(Filesystem $disk, string $sourceDir, string $targetDir): void
    {
        $directories = ['p2', 'include', 'archives'];

        foreach ($directories as $dir) {
            $sourcePath = rtrim($sourceDir, '/').'/'.$dir;

            if (! $disk->exists($sourcePath)) {
                continue;
            }

            foreach ($disk->allFiles($sourcePath) as $file) {
                $relativePath = str_replace($sourceDir.'/', '', $file);
                $targetPath = rtrim($targetDir, '/').'/'.$relativePath;

                $disk->put($targetPath, $disk->get($file));
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function recalculateIncludes(Filesystem $disk, array $includes, string $outputDir): array
    {
        $result = [];

        foreach ($includes as $includeFile => $meta) {
            $fullPath = rtrim($outputDir, '/').'/'.$includeFile;

            if ($disk->exists($fullPath)) {
                $sha1 = sha1($disk->get($fullPath));
                $result[$includeFile] = ['sha1' => $sha1];
            } else {
                $result[$includeFile] = $meta;
            }
        }

        return $result;
    }
}
