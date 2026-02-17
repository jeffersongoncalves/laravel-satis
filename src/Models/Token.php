<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JeffersonGoncalves\LaravelSatis\Concerns\GenerateCode;
use JeffersonGoncalves\LaravelSatis\Concerns\HasTenancy;
use JeffersonGoncalves\LaravelSatis\Database\Factories\TokenFactory;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class Token extends Model implements AuthenticatableContract
{
    use Authenticatable;
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

    public static function getColumnCode(): array
    {
        return ['token'];
    }

    public static function getLengthCode(): array
    {
        return [
            'token' => 64,
        ];
    }

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'tokens';
    }

    public function packages(): BelongsToMany
    {
        $packageModel = ModelResolver::package();

        return $this->belongsToMany(
            $packageModel,
            (config('satis.table_prefix') ?? '').'package_token',
            'token_id',
            'package_id'
        )->withTimestamps();
    }

    public function getAuthIdentifierName(): string
    {
        return 'token';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->token;
    }

    public function getAuthPasswordName(): string
    {
        return 'token';
    }
}
