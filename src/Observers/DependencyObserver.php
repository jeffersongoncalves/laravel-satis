<?php

namespace JeffersonGoncalves\LaravelSatis\Observers;

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class DependencyObserver
{
    public function creating(Dependency $dependency): void
    {
        if (empty($dependency->type)) {
            $packagistModel = ModelResolver::packagist();
            $exists = $packagistModel::where('name', $dependency->name)->exists();
            $dependency->type = $exists ? DependencyType::Public : DependencyType::Private;
        }
    }

    public function created(Dependency $dependency): void
    {
        $this->clearCache();
    }

    public function deleted(Dependency $dependency): void
    {
        $this->clearCache();
    }

    protected function clearCache(): void
    {
        Cache::forget('laravel-satis:dependencies');
        Cache::forget('laravel-satis:dependencies-count');
    }
}
