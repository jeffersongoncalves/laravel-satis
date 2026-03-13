<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelSatis\Database\Factories\PackageReleaseFactory;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageReleaseContract;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

/**
 * @property int $id
 * @property int $package_id
 * @property string $version
 * @property Carbon|null $time
 * @property string|null $type
 * @property string|null $description
 * @property string|null $homepage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $name
 * @property-read Package $package
 * @property-read Collection<int, DependencyPackageRelease> $packageReleaseRequires
 * @property-read Collection<int, Dependency> $dependencies
 */
class PackageRelease extends Model implements PackageReleaseContract
{
    use HasFactory;

    protected static function newFactory(): PackageReleaseFactory
    {
        return PackageReleaseFactory::new();
    }

    protected $casts = [
        'time' => 'datetime',
    ];

    protected $fillable = [
        'package_id',
        'version',
        'time',
        'type',
        'description',
        'homepage',
    ];

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'package_releases';
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::package());
    }

    public function packageReleaseRequires(): HasMany
    {
        return $this->hasMany(ModelResolver::dependencyPackageRelease());
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->package->name.' - '.$this->version);
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            ModelResolver::dependency(),
            (config('satis.table_prefix') ?? '').'dependency_package_release',
            'package_release_id',
            'dependency_id'
        )->withPivot('version', 'package_id')->withTimestamps();
    }
}
