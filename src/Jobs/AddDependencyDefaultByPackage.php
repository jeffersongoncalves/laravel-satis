<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class AddDependencyDefaultByPackage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        protected Model $package,
        protected string $package_dependency
    ) {
        $queueConfig = config('satis.queue');

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

        $packageModel::create([
            'name' => $this->package_dependency,
            'type' => $this->package->type,
            'url' => $this->package->url,
            'username' => $this->package->username,
            'password' => $this->package->password,
        ]);
    }
}
