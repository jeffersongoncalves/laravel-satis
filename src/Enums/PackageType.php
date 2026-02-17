<?php

namespace JeffersonGoncalves\LaravelSatis\Enums;

enum PackageType: string
{
    case Composer = 'composer';
    case Github = 'github';

    public static function of(int|string|self $value): self
    {
        return self::tryFrom($value instanceof self ? $value->value : $value);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Composer => __('laravel-satis::package.type.composer'),
            self::Github => __('laravel-satis::package.type.github'),
        };
    }
}
