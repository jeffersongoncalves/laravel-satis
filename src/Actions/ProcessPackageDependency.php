<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Jobs\AddDependencyDefaultByPackage;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class ProcessPackageDependency
{
    public function execute(PackageRelease $release, array $requires, ?Model $package = null): void
    {
        $dependencyModel = ModelResolver::dependency();

        foreach ($requires as $require => $version) {
            if ($this->shouldSkip($require)) {
                continue;
            }

            $version = is_array($version) ? implode(',', $version) : $version;

            $dependency = $dependencyModel::firstOrCreate([
                'name' => $require,
            ], [
                'versions' => [$version],
            ]);

            $versions = collect($dependency->versions ?? []);
            if (! $versions->contains($version)) {
                $versions->push($version);
                $dependency->update([
                    'versions' => $versions->unique()->values()->all(),
                ]);
            }

            $release->dependencies()->syncWithoutDetaching([
                $dependency->id => [
                    'version' => $version,
                    'package_id' => $release->package_id,
                ],
            ]);

            if ($package && $dependency->type->value === DependencyType::Private->value) {
                AddDependencyDefaultByPackage::dispatch($package, $require);
            }
        }
    }

    protected function shouldSkip(string $name): bool
    {
        $skipPrefixes = ['php', 'ext-', 'lib-', 'composer-plugin-api'];

        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
