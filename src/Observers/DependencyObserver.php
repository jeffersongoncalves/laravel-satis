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
        if (str($dependency->name)->contains('/')) {
            $packagistModel = ModelResolver::packagist();
            $dependency->setAttribute('type', $packagistModel::getDependencyType($dependency->name));
        } else {
            $dependency->setAttribute('type', DependencyType::Public);
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
