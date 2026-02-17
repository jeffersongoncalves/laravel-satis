<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface TokenContract extends AuthenticatableContract
{
    public function packages(): BelongsToMany;

    /**
     * @return array<int, string>
     */
    public static function getColumnCode(): array;

    /**
     * @return array<string, int>
     */
    public static function getLengthCode(): array;

    public function getAuthPasswordName(): string;
}
