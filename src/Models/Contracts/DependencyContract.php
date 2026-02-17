<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface DependencyContract
{
    public function packageReleaseRequires(): HasMany;

    public function packageReleases(): BelongsToMany;
}
