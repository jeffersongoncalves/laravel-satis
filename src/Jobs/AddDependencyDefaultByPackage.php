<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class AddDependencyDefaultByPackage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        protected Model $package,
        protected string $package_dependency
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

        if ($packageModel::query()->where('name', $this->package_dependency)->exists()) {
            return;
        }

        /** @var Package $package */
        $package = $this->package;

        $packageModel::create([
            'name' => $this->package_dependency,
            'type' => $package->type,
            'credential_id' => $package->credential_id,
        ]);
    }
}
