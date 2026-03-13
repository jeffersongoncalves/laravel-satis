<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JeffersonGoncalves\LaravelSatis\Concerns\HasTenancy;
use JeffersonGoncalves\LaravelSatis\Database\Factories\CredentialFactory;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\CredentialContract;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $email
 * @property string $password
 * @property bool $is_validated
 * @property \Illuminate\Support\Carbon|null $validated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $display_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Package> $packages
 */
class Credential extends Model implements CredentialContract
{
    use HasFactory;
    use HasTenancy;

    protected static function newFactory(): CredentialFactory
    {
        return CredentialFactory::new();
    }

    protected $fillable = [
        'name',
        'url',
        'email',
        'password',
        'is_validated',
        'validated_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'credentials';
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ModelResolver::package());
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->name} ({$this->email})");
    }
}
