<?php

namespace JeffersonGoncalves\LaravelSatis\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTenancy
{
    public static function bootHasTenancy(): void
    {
        if (! config('satis.tenancy.enabled')) {
            return;
        }

        $foreignKey = config('satis.tenancy.foreign_key');

        static::addGlobalScope('satis-tenant', function (Builder $query) use ($foreignKey) {
            $resolver = config('satis.tenancy.resolver');

            if ($resolver && ($tenantId = call_user_func($resolver))) {
                $query->where($foreignKey, $tenantId);
            }
        });

        static::creating(function (Model $model) use ($foreignKey) {
            $resolver = config('satis.tenancy.resolver');

            if ($resolver && ($tenantId = call_user_func($resolver))) {
                $model->{$foreignKey} = $tenantId;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        $model = config('satis.tenancy.model');
        $foreignKey = config('satis.tenancy.foreign_key');

        return $this->belongsTo($model, $foreignKey);
    }
}
