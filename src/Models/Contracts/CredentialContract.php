<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface CredentialContract
{
    public function packages(): HasMany;
}
