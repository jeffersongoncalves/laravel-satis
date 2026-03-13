<?php

namespace JeffersonGoncalves\LaravelSatis\Support;

use InvalidArgumentException;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\CredentialContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\DependencyContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\DependencyPackageReleaseContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageDownloadContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageReleaseContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackageTokenContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\PackagistContract;
use JeffersonGoncalves\LaravelSatis\Models\Contracts\TokenContract;

class ModelResolver
{
    /** @var array<string, string> */
    protected static array $cache = [];

    public static function credential(): string
    {
        return static::resolve('credential', CredentialContract::class);
    }

    public static function package(): string
    {
        return static::resolve('package', PackageContract::class);
    }

    public static function token(): string
    {
        return static::resolve('token', TokenContract::class);
    }

    public static function dependency(): string
    {
        return static::resolve('dependency', DependencyContract::class);
    }

    public static function packageRelease(): string
    {
        return static::resolve('package_release', PackageReleaseContract::class);
    }

    public static function packageDownload(): string
    {
        return static::resolve('package_download', PackageDownloadContract::class);
    }

    public static function dependencyPackageRelease(): string
    {
        return static::resolve('dependency_package_release', DependencyPackageReleaseContract::class);
    }

    public static function packageToken(): string
    {
        return static::resolve('package_token', PackageTokenContract::class);
    }

    public static function packagist(): string
    {
        return static::resolve('packagist', PackagistContract::class);
    }

    /**
     * @param  class-string  $contract
     * @return class-string
     *
     * @throws InvalidArgumentException
     */
    protected static function resolve(string $key, string $contract): string
    {
        if (isset(static::$cache[$key])) {
            return static::$cache[$key];
        }

        /** @var class-string|null $model */
        $model = config("satis.models.{$key}");

        if (! $model || ! class_exists($model)) {
            throw new InvalidArgumentException(
                "Model class for [{$key}] does not exist: {$model}"
            );
        }

        if (! is_a($model, $contract, true)) {
            throw new InvalidArgumentException(
                "Model [{$model}] must implement [{$contract}]."
            );
        }

        return static::$cache[$key] = $model;
    }

    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
