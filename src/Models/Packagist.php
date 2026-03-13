<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackagistContract;

/**
 * @property int $id
 * @property string $name
 * @property DependencyType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Packagist extends Model implements PackagistContract
{
    protected $fillable = [
        'name',
        'type',
    ];

    protected $casts = [
        'type' => DependencyType::class,
    ];

    public function getTable(): string
    {
        return (config('satis.table_prefix') ?? '').'packagists';
    }

    public static function getDependencyType(string $name): DependencyType
    {
        if ($packagist = static::query()->where('name', $name)->first()) {
            return $packagist->type;
        }

        $packagist = static::create([
            'name' => $name,
            'type' => static::getRepoByPackagist($name),
        ]);

        return $packagist->type;
    }

    private static function getRepoByPackagist(string $name): DependencyType
    {
        try {
            return Http::get("https://repo.packagist.org/p2/{$name}.json")->successful()
                ? DependencyType::Public
                : DependencyType::Private;
        } catch (ConnectionException $e) {
            return DependencyType::Private;
        }
    }
}
