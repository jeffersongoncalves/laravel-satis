<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;

interface PackagistContract
{
    public static function getDependencyType(string $name): DependencyType;
}
