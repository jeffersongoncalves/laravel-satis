<?php

namespace JeffersonGoncalves\LaravelSatis\Observers;

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelSatis\Models\Package;

class PackageObserver
{
    public function creating(Package $package): void
    {
        if (empty($package->webhook_secret)) {
            $package->webhook_secret = $package::generateCode('webhook_secret');
        }

        if (empty($package->reference)) {
            $package->reference = $package::generateCode('reference');
        }
    }

    public function created(Package $package): void
    {
        $this->clearCache();
    }

    public function updated(Package $package): void
    {
        if ($package->isDirty(['username', 'password', 'url'])) {
            $package->updateQuietly([
                'is_credentials_validated' => false,
                'credentials_validated_at' => null,
            ]);
        }

        $this->clearCache();
    }

    public function deleted(Package $package): void
    {
        $this->clearCache();
    }

    protected function clearCache(): void
    {
        Cache::forget('laravel-satis:packages');
        Cache::forget('laravel-satis:packages-count');
    }
}
