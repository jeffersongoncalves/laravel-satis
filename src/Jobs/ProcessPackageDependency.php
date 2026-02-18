<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\ProcessPackageDependency as ProcessPackageDependencyAction;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class ProcessPackageDependency implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        protected Package $package
    ) {
        $queueConfig = config('satis.queue');

        $this->timeout = $queueConfig['timeout'] ?? 86400;

        if ($queueConfig['connection'] ?? null) {
            $this->onConnection($queueConfig['connection']);
        }

        if ($queueConfig['queue_name'] ?? null) {
            $this->onQueue($queueConfig['queue_name']);
        }
    }

    public function handle(ProcessPackageDependencyAction $action): void
    {
        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        $tenantPrefix = '';
        if (config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tenantId = $this->package->{$fk} ?? null;
            if ($tenantId) {
                $tenantPrefix = $tenantId.'/';
            }
        }

        $composerCachePath = $storagePath.'/'.$tenantPrefix.'composer/cache/repo/'.$this->package->folder;

        $providerFile = $composerCachePath.'/provider-'.$this->package->name_provider.'.json';
        $packagesFile = $composerCachePath.'/packages.json';

        if ($disk->exists($providerFile)) {
            $filename = $providerFile;
        } elseif ($disk->exists($packagesFile)) {
            $filename = $packagesFile;
        } else {
            return;
        }

        $content = json_decode($disk->get($filename), true);
        $packagesData = $content['packages'] ?? [];

        if (! isset($packagesData[$this->package->name])) {
            return;
        }

        $releaseModel = ModelResolver::packageRelease();

        foreach ($packagesData[$this->package->name] as $versionData) {
            $version = $versionData['version'] ?? null;

            if (! $version) {
                continue;
            }

            $release = $releaseModel::updateOrCreate(
                [
                    'package_id' => $this->package->id,
                    'version' => $version,
                ],
                [
                    'time' => ! empty($versionData['time']) ? $versionData['time'] : now()->toIso8601String(),
                    'type' => ! empty($versionData['type']) ? $versionData['type'] : 'library',
                    'description' => ! empty($versionData['description']) ? $versionData['description'] : '',
                    'homepage' => ! empty($versionData['homepage']) ? $versionData['homepage'] : '',
                ]
            );

            $requires = $versionData['require'] ?? [];
            $action->execute($release, $requires, $this->package);
        }
    }
}
