<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

/**
 * @property int $id
 * @property int $package_id
 * @property string $version
 * @property int $downloads
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Package $package
 */
class PackageDownload extends Model
{
    protected $fillable = [
        'package_id',
        'version',
        'downloads',
    ];

    protected $casts = [
        'downloads' => 'integer',
    ];

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'package_downloads';
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::package());
    }
}
