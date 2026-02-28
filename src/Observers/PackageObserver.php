<?php

namespace JeffersonGoncalves\LaravelSatis\Observers;

use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Jobs\ValidatePackageCredentialsJob;
use JeffersonGoncalves\LaravelSatis\Models\Package;

class PackageObserver
{
    public function creating(Package $package): void
    {
        if ($package->type === PackageType::Github && empty($package->webhook_secret)) {
            $package->webhook_secret = $package::generateCode('webhook_secret');
        }

        if (empty($package->reference)) {
            $package->reference = $package::generateCode('reference');
        }
    }

    public function created(Package $package): void
    {
        ValidatePackageCredentialsJob::dispatch($package);
    }

    public function updated(Package $package): void
    {
        if ($package->isDirty('is_credentials_validated') && $package->is_credentials_validated) {
            $package->tokens->each(function ($token) {
                SyncTokenPackages::dispatch($token);
            });
        }

        if ($package->isDirty(['username', 'password', 'url'])) {
            $package->updateQuietly([
                'is_credentials_validated' => false,
                'credentials_validated_at' => null,
            ]);
        }
    }
}
