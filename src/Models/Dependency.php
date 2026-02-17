<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class Dependency extends Model
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
        return config('satis.table_prefix', 'satis_').'dependencies';
    }

    public function packageReleases(): BelongsToMany
    {
        return $this->belongsToMany(
            ModelResolver::packageRelease(),
            config('satis.table_prefix', 'satis_').'dependency_package_release',
            'dependency_id',
            'package_release_id'
        )->withPivot('version', 'package_id')->withTimestamps();
    }
}
