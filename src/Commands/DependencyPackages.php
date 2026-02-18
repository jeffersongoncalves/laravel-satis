<?php

namespace JeffersonGoncalves\LaravelSatis\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LaravelSatis\Jobs\ProcessPackageDependency;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class DependencyPackages extends Command
{
    protected $signature = 'dependency:packages';

    protected $description = 'Process and sync package dependencies from Satis builds';

    public function handle(): int
    {
        $packageModel = ModelResolver::package();
        $packages = $packageModel::withoutGlobalScopes()->get();

        $packages->each(function ($package) {
            ProcessPackageDependency::dispatch($package);
        });

        $this->info("Dispatched dependency processing for {$packages->count()} packages.");

        return self::SUCCESS;
    }
}
