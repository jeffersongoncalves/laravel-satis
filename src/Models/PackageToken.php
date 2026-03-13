<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageTokenContract;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

/**
 * @property int $id
 * @property int $package_id
 * @property int $token_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Package $package
 * @property-read Token $token
 */
class PackageToken extends Pivot implements PackageTokenContract
{
    protected $fillable = [
        'package_id',
        'token_id',
    ];

    public $incrementing = true;

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'package_token';
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
