<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\ValidatePackageCredentials;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class ValidateTenantSatisBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        protected ?int $tenantId = null
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

    public function handle(ValidatePackageCredentials $validator): void
    {
        $packageModel = ModelResolver::package();
        $query = $packageModel::query();

        if ($this->tenantId && config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $query->withoutGlobalScope('satis-tenant')->where($fk, $this->tenantId);
        }

        $packages = $query->get();

        foreach ($packages as $package) {
            $validator->execute($package);
        }

        if ($this->shouldRebuild($packageModel)) {
            SyncTenantPackages::dispatch($this->tenantId);
        }

        $tokenModel = ModelResolver::token();
        $tokensQuery = $tokenModel::query();

        if ($this->tenantId && config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tokensQuery->withoutGlobalScope('satis-tenant')->where($fk, $this->tenantId);
        }

        $tokens = $tokensQuery->get();

        foreach ($tokens as $token) {
            ValidateTokenSatisBuild::dispatch($token);
        }
    }

    protected function shouldRebuild(string $packageModel): bool
    {
        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');
        $tenantPrefix = $this->tenantId ? $this->tenantId.'/' : '';
        $buildPath = $storagePath.'/'.$tenantPrefix.'tenant';
        $packagesJson = $buildPath.'/packages.json';

        if (! $disk->exists($packagesJson)) {
            return true;
        }

        $lastBuildTime = $disk->lastModified($packagesJson);

        $query = $packageModel::query();

        if ($this->tenantId && config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $query->withoutGlobalScope('satis-tenant')->where($fk, $this->tenantId);
        }

        $lastPackageUpdate = $query->max('updated_at');

        if ($lastPackageUpdate && strtotime($lastPackageUpdate) > $lastBuildTime) {
            return true;
        }

        return false;
    }
}
