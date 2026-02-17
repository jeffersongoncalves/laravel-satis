<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface PackageReleaseContract
{
    public function package(): BelongsTo;

    public function packageReleaseRequires(): HasMany;

    public function dependencies(): BelongsToMany;
}
