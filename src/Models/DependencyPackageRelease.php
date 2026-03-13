<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\DependencyPackageReleaseContract;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

/**
 * @property int $id
 * @property int $package_id
 * @property int $package_release_id
 * @property int $dependency_id
 * @property string|null $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Package $package
 * @property-read PackageRelease $packageRelease
 * @property-read Dependency $dependency
 */
class DependencyPackageRelease extends Pivot implements DependencyPackageReleaseContract
{
    protected $fillable = [
        'package_id',
        'package_release_id',
        'dependency_id',
        'version',
    ];

    public $incrementing = true;

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'dependency_package_release';
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::package());
    }

    public function packageRelease(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::packageRelease());
    }

    public function dependency(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::dependency());
    }
}
