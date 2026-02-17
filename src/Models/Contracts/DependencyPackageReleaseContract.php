<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface DependencyPackageReleaseContract
{
    public function package(): BelongsTo;

    public function packageRelease(): BelongsTo;

    public function dependency(): BelongsTo;
}
