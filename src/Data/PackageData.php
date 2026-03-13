<?php

namespace JeffersonGoncalves\LaravelSatis\Data;

class PackageData
{
    public function __construct(
        public readonly string $name,
        public readonly string $version = '*',
    ) {}
}
