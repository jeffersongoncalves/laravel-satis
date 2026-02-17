<?php

namespace JeffersonGoncalves\LaravelSatis\Observers;

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
}
