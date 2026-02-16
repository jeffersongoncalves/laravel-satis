<?php

namespace JeffersonGoncalves\LaravelSatis\Support;

class ModelResolver
{
    public static function package(): string
    {
        return config('laravel-satis.models.package');
    }

    public static function token(): string
    {
        return config('laravel-satis.models.token');
    }

    public static function dependency(): string
    {
        return config('laravel-satis.models.dependency');
    }

    public static function packageRelease(): string
    {
        return config('laravel-satis.models.package_release');
    }

    public static function packageDownload(): string
    {
        return config('laravel-satis.models.package_download');
    }

    public static function packagist(): string
    {
        return config('laravel-satis.models.packagist');
    }
}
