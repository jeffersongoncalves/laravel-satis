<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Models\Token;

class ValidateTokenSatisBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        protected Token $token
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

    public function handle(): void
    {
        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        $tenantPrefix = '';
        if (config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tenantId = $this->token->{$fk} ?? null;
            if ($tenantId) {
                $tenantPrefix = $tenantId.'/';
            }
        }

        $tenantBuildPath = $storagePath.'/'.$tenantPrefix.'tenant';
        $tenantPackagesJson = $tenantBuildPath.'/packages.json';

        if (! $disk->exists($tenantPackagesJson)) {
            return;
        }

        $buildPath = $storagePath.'/'.$tenantPrefix.$this->token->id;
        $packagesJson = $buildPath.'/packages.json';

        if (! $disk->exists($packagesJson)) {
            SyncTokenPackages::dispatch($this->token);

            return;
        }

        $content = json_decode($disk->get($packagesJson), true);

        if (empty($content) || ! isset($content['packages'])) {
            SyncTokenPackages::dispatch($this->token);

            return;
        }

        $lastBuildTime = $disk->lastModified($packagesJson);

        $lastPivotUpdate = $this->token->packages()
            ->withPivot('updated_at')
            ->get()
            ->max('pivot.updated_at');

        if ($lastPivotUpdate && strtotime($lastPivotUpdate) > $lastBuildTime) {
            SyncTokenPackages::dispatch($this->token);
        }
    }
}
