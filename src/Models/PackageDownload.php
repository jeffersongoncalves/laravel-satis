<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

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
        return config('satis.table_prefix', 'satis_').'package_downloads';
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::package());
    }
}
