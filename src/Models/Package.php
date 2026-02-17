<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JeffersonGoncalves\LaravelSatis\Concerns\GenerateCode;
use JeffersonGoncalves\LaravelSatis\Concerns\HasTenancy;
use JeffersonGoncalves\LaravelSatis\Database\Factories\PackageFactory;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class Package extends Model
{
    use GenerateCode;
    use HasFactory;
    use HasTenancy;

    protected static function newFactory(): PackageFactory
    {
        return PackageFactory::new();
    }

    protected $fillable = [
        'name',
        'type',
        'url',
        'is_dev',
        'username',
        'password',
        'webhook_secret',
        'reference',
        'is_credentials_validated',
        'credentials_validated_at',
    ];

    protected $casts = [
        'type' => PackageType::class,
        'is_dev' => 'boolean',
        'is_credentials_validated' => 'boolean',
        'credentials_validated_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'username',
        'webhook_secret',
    ];

    public static function getColumnCode(): array
    {
        return ['webhook_secret', 'reference'];
    }

    public static function getLengthCode(): array
    {
        return [
            'webhook_secret' => 40,
            'reference' => 20,
        ];
    }

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'packages';
    }

    public function tokens(): BelongsToMany
    {
        $tokenModel = ModelResolver::token();

        return $this->belongsToMany(
            $tokenModel,
            (config('satis.table_prefix') ?? '').'package_token',
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

    protected function folder(): Attribute
    {
        return Attribute::get(fn (): string => str($this->url)->replace(':', '-')->replace('/', '-')->toString());
    }

    protected function nameProvider(): Attribute
    {
        return Attribute::get(fn (): string => str($this->name)->replace('/', '~')->toString());
    }

    protected function composerCommand(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_dev
            ? "composer require --dev {$this->name}"
            : "composer require {$this->name}"
        );
    }

    protected function webhookUrl(): Attribute
    {
        return Attribute::get(fn (): string => route('webhooks.github', ['package' => $this->reference]));
    }
}
