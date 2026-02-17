<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\CreateAuthJson;
use JeffersonGoncalves\LaravelSatis\Actions\SanitizeSatisPackages;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;
use JeffersonGoncalves\LaravelSatis\Support\SatisConfig;

class SyncTenantPackages implements ShouldQueue
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

    public function handle(): void
    {
        $packageModel = ModelResolver::package();
        $tokenModel = ModelResolver::token();

        $query = $packageModel::query();

        if ($this->tenantId && config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $query->withoutGlobalScope('satis-tenant')->where($fk, $this->tenantId);
        }

        $packages = $query->get();

        if ($packages->isEmpty()) {
            return;
        }

        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');
        $tenantPrefix = $this->tenantId ? $this->tenantId.'/' : '';
        $buildPath = $storagePath.'/'.$tenantPrefix.'tenant';

        $satisConfig = SatisConfig::make()
            ->setPackages($packages)
            ->setHomepage(url(config('satis.routes.composer_prefix') ?? '/'));

        $configPath = $buildPath.'/satis.json';
        $disk->put($configPath, $satisConfig->toJson());

        $satisBinary = config('satis.satis_binary') ?? base_path('vendor/bin/satis');
        $fullConfigPath = $disk->path($configPath);
        $fullBuildPath = $disk->path($buildPath);

        $composerHomePath = $storagePath.'/'.$tenantPrefix.'composer';
        $composerHome = app(CreateAuthJson::class)->execute($packages, $composerHomePath);

        $result = Process::timeout($this->timeout)
            ->env(['COMPOSER_HOME' => $composerHome])
            ->run([
                'php', $satisBinary, 'build', $fullConfigPath, $fullBuildPath,
            ]);

        if (! $result->successful()) {
            Log::error('Satis build failed', [
                'tenant_id' => $this->tenantId,
                'output' => $result->errorOutput(),
            ]);

            return;
        }

        app(SanitizeSatisPackages::class)->sanitizeDirectory($buildPath, $disk);

        ProcessPackageDependency::dispatch();

        $tokensQuery = $tokenModel::query();

        if ($this->tenantId && config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tokensQuery->withoutGlobalScope('satis-tenant')->where($fk, $this->tenantId);
        }

        $tokens = $tokensQuery->with('packages')->get();

        foreach ($tokens as $token) {
            SyncTokenPackages::dispatch($token);
        }
    }
}
