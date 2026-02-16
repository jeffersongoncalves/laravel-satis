<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JeffersonGoncalves\LaravelSatis\Concerns\GenerateCode;
use JeffersonGoncalves\LaravelSatis\Concerns\HasTenancy;
use JeffersonGoncalves\LaravelSatis\Database\Factories\TokenFactory;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class Token extends Model implements Authenticatable
{
    use GenerateCode;
    use HasFactory;
    use HasTenancy;

    protected static function newFactory(): TokenFactory
    {
        return TokenFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'token',
    ];

    protected $hidden = [
        'token',
    ];

    public function getTable(): string
    {
        return config('laravel-satis.table_prefix', 'satis_').'tokens';
    }

    public function packages(): BelongsToMany
    {
        $packageModel = ModelResolver::package();

        return $this->belongsToMany(
            $packageModel,
            config('laravel-satis.table_prefix', 'satis_').'package_token',
            'token_id',
            'package_id'
        )->withTimestamps();
    }

    public function getAuthIdentifierName(): string
    {
        return 'token';
    }

    public function getAuthIdentifier(): string
    {
        return $this->token;
    }

    public function getAuthPassword(): string
    {
        return $this->token;
    }

    public function getAuthPasswordName(): string
    {
        return 'token';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        //
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
