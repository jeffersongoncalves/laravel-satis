<?php

namespace JeffersonGoncalves\LaravelSatis\Data;

class RepositoryData
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $url,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
