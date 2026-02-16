<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class PackageToken extends Pivot
{
    protected $fillable = [
        'package_id',
        'token_id',
    ];

    public $incrementing = true;

    public function getTable(): string
    {
        return config('laravel-satis.table_prefix', 'satis_').'package_token';
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::package());
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::token());
    }
}
