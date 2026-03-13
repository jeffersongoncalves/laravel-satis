<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelSatis\Concerns\GenerateCode;
use JeffersonGoncalves\LaravelSatis\Concerns\HasTenancy;
use JeffersonGoncalves\LaravelSatis\Database\Factories\PackageFactory;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageContract;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

/**
 * @property int $id
 * @property string $name
 * @property PackageType $type
 * @property int|null $credential_id
 * @property bool $is_dev
 * @property string|null $webhook_secret
 * @property string|null $reference
 * @property bool $is_credentials_validated
 * @property Carbon|null $credentials_validated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $folder
 * @property-read string $name_provider
 * @property-read string $composer_command
 * @property-read string $webhook_url
 * @property-read Credential|null $credential
 * @property-read Collection<int, Token> $tokens
 * @property-read PackageRelease|null $packageRelease
 * @property-read Collection<int, PackageRelease> $packageReleases
 * @property-read Collection<int, PackageDownload> $packageDownloads
 */
class Package extends Model implements PackageContract
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
        'credential_id',
        'is_dev',
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
    ];

    public static function getColumnCode(): array
    {
        return ['webhook_secret', 'reference'];
    }

    public static function getLengthCode(): array
    {
        return [
            'webhook_secret' => 64,
            'reference' => 32,
        ];
    }

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'packages';
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::credential());
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

    public function packageRelease(): HasOne
    {
        return $this->hasOne(ModelResolver::packageRelease())->latest('version');
    }

    public function packageReleases(): HasMany
    {
        return $this->hasMany(ModelResolver::packageRelease());
    }

    public function packageDownloads(): HasMany
    {
        return $this->hasMany(ModelResolver::packageDownload());
    }

    protected function folder(): Attribute
    {
        return Attribute::get(function (): string {
            $credential = $this->credential;

            if (! $credential) {
                return '';
            }

            if ($this->type === PackageType::Github) {
                $url = str($credential->url)
                    ->prepend('https://')
                    ->replaceFirst('git@', rawurlencode($credential->email).':***@')
                    ->replaceLast(':', '/')
                    ->toString();
            } else {
                $parsed = parse_url($credential->url);
                $scheme = ($parsed['scheme'] ?? 'https').'://';
                $host = $parsed['host'] ?? '';
                $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
                $path = $parsed['path'] ?? '';
                $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
                $url = $scheme.rawurlencode($credential->email).':***@'.$host.$port.$path.$query;
            }

            return preg_replace('{[^a-z0-9.]}i', '-', $url);
        });
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
