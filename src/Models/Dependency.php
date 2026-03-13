<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\DependencyContract;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

/**
 * @property int $id
 * @property string $name
 * @property array|null $versions
 * @property DependencyType|null $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DependencyPackageRelease> $packageReleaseRequires
 * @property-read Collection<int, PackageRelease> $packageReleases
 */
class Dependency extends Model implements DependencyContract
{
    protected $fillable = [
        'name',
        'versions',
        'type',
    ];

    protected $casts = [
        'versions' => 'array',
        'type' => DependencyType::class,
    ];

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'dependencies';
    }

    public function packageReleaseRequires(): HasMany
    {
        return $this->hasMany(ModelResolver::dependencyPackageRelease());
    }

    public function packageReleases(): BelongsToMany
    {
        return $this->belongsToMany(
            ModelResolver::packageRelease(),
            (config('satis.table_prefix') ?? '').'dependency_package_release',
            'dependency_id',
            'package_release_id'
        )->withPivot('version', 'package_id')->withTimestamps();
    }
}
