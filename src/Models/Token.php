<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $attributes = [
        'email' => 'token',
    ];

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

    protected function composerCommand(): Attribute
    {
        return Attribute::get(function (): string {
            $host = str(config('app.url'))->replace(['http://', 'https://'], '')->toString();

            return "composer global config http-basic.{$host} token {$this->token}";
        });
    }

    protected function composerRepository(): Attribute
    {
        return Attribute::get(function (): string {
            $host = config('app.url');
            $name = str($host)->replace(['http://', 'https://'], '')->toString();

            return "composer config repositories.$name composer $host";
        });
    }

    public function getAuthPasswordName(): string
    {
        return 'token';
    }
}
