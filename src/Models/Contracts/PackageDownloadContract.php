<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface PackageDownloadContract
{
    public function package(): BelongsTo;
}
