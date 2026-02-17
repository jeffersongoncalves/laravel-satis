<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface PackageTokenContract
{
    public function package(): BelongsTo;

    public function token(): BelongsTo;
}
