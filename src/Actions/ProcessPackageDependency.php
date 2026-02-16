<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class ProcessPackageDependency
{
    public function execute(PackageRelease $release, array $requires): void
    {
        $dependencyModel = ModelResolver::dependency();

        foreach ($requires as $name => $version) {
            if ($this->shouldSkip($name)) {
                continue;
            }

            $dependency = $dependencyModel::firstOrCreate(
                ['name' => $name],
                ['type' => $this->resolveType($name)]
            );

            $versions = $dependency->versions ?? [];
            if (! in_array($version, $versions)) {
                $versions[] = $version;
                $dependency->update(['versions' => $versions]);
            }

            $release->dependencies()->syncWithoutDetaching([
                $dependency->id => [
                    'version' => $version,
                    'package_id' => $release->package_id,
                ],
            ]);
        }
    }

    protected function shouldSkip(string $name): bool
    {
        $skipPrefixes = ['php', 'ext-', 'lib-'];

        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function resolveType(string $name): DependencyType
    {
        $packagistModel = ModelResolver::packagist();

        $exists = $packagistModel::where('name', $name)->exists();

        return $exists ? DependencyType::Public : DependencyType::Private;
    }
}
