<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelSatis\Actions\ValidatePackageCredentials;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class ValidateTenantSatisBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        protected ?int $tenantId = null
    ) {
        $queueConfig = config('laravel-satis.queue');

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

        if ($this->tenantId && config('laravel-satis.tenancy.enabled')) {
            $fk = config('laravel-satis.tenancy.foreign_key');
            $query->withoutGlobalScope('satis-tenant')->where($fk, $this->tenantId);
        }

        $packages = $query->get();

        foreach ($packages as $package) {
            $validator->execute($package);
        }

        $tokenModel = ModelResolver::token();
        $tokensQuery = $tokenModel::query();

        if ($this->tenantId && config('laravel-satis.tenancy.enabled')) {
            $fk = config('laravel-satis.tenancy.foreign_key');
            $tokensQuery->withoutGlobalScope('satis-tenant')->where($fk, $this->tenantId);
        }

        $tokens = $tokensQuery->get();

        foreach ($tokens as $token) {
            ValidateTokenSatisBuild::dispatch($token);
        }
    }
}
