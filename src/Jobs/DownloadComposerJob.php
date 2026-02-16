<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class DownloadComposerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected int $packageId,
        protected string $version
    ) {
        $queueConfig = config('laravel-satis.queue');

        if ($queueConfig['connection'] ?? null) {
            $this->onConnection($queueConfig['connection']);
        }

        if ($queueConfig['queue_name'] ?? null) {
            $this->onQueue($queueConfig['queue_name']);
        }
    }

    public function handle(): void
    {
        $downloadModel = ModelResolver::packageDownload();

        $download = $downloadModel::firstOrCreate(
            [
                'package_id' => $this->packageId,
                'version' => $this->version,
            ],
            ['downloads' => 0]
        );

        $download->increment('downloads');
    }
}
