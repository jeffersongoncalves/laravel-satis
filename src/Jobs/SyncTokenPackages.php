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
use JeffersonGoncalves\LaravelSatis\Models\Token;
use JeffersonGoncalves\LaravelSatis\Support\SatisConfig;

class SyncTokenPackages implements ShouldQueue
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
        $packages = $this->token->packages;

        if ($packages->isEmpty()) {
            return;
        }

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

        $buildPath = $storagePath.'/'.$tenantPrefix.$this->token->id;

        $routeParams = [];
        if (config('satis.tenancy.enabled') && $tenantPrefix) {
            $routeParams['tenant'] = rtrim($tenantPrefix, '/');
        }

        $satisConfig = SatisConfig::make()
            ->setPackages($packages)
            ->setHomepage(url(config('satis.routes.composer_prefix') ?? '/'))
            ->setNotifyBatch(route('composer.downloads', $routeParams));

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
                'php', $satisBinary, 'build', $fullConfigPath, $fullBuildPath, '--skip-errors',
            ]);

        if (! $result->successful()) {
            Log::error('Satis token build failed', [
                'token_id' => $this->token->id,
                'output' => $result->errorOutput(),
            ]);

            return;
        }

        app(SanitizeSatisPackages::class)->sanitizeDirectory($buildPath, $disk);
    }
}
