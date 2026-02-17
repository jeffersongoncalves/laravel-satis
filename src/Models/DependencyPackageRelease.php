<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class DependencyPackageRelease extends Pivot
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
