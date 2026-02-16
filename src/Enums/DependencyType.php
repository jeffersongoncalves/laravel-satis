<?php

namespace JeffersonGoncalves\LaravelSatis\Enums;

enum DependencyType: string
{
    case Public = 'public';
    case Private = 'private';

    public function getLabel(): string
    {
        return match ($this) {
            self::Public => __('laravel-satis::dependency.type.public'),
            self::Private => __('laravel-satis::dependency.type.private'),
        };
    }
}
