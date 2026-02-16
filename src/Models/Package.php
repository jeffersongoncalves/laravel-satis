<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JeffersonGoncalves\LaravelSatis\Concerns\GenerateCode;
use JeffersonGoncalves\LaravelSatis\Concerns\HasTenancy;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class Package extends Model
{
    use GenerateCode;
    use HasFactory;
    use HasTenancy;

    protected $fillable = [
        'name',
        'type',
        'url',
        'username',
        'password',
        'webhook_secret',
        'reference',
        'is_credentials_validated',
        'credentials_validated_at',
    ];

    protected $casts = [
        'type' => PackageType::class,
        'is_credentials_validated' => 'boolean',
        'credentials_validated_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'username',
        'webhook_secret',
    ];

    public function getTable(): string
    {
        return config('laravel-satis.table_prefix', 'satis_').'packages';
    }

    public function tokens(): BelongsToMany
    {
        $tokenModel = ModelResolver::token();

        return $this->belongsToMany(
            $tokenModel,
            config('laravel-satis.table_prefix', 'satis_').'package_token',
            'package_id',
            'token_id'
        )->withTimestamps();
    }

    public function releases(): HasMany
    {
        return $this->hasMany(ModelResolver::packageRelease());
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ModelResolver::packageDownload());
    }
}
