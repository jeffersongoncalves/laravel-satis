<?php

namespace JeffersonGoncalves\LaravelSatis\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface PackageContract
{
    public function tokens(): BelongsToMany;

    public function packageRelease(): HasOne;

    public function packageReleases(): HasMany;

    public function packageDownloads(): HasMany;

    /**
     * @return array<int, string>
     */
    public static function getColumnCode(): array;

    /**
     * @return array<string, int>
     */
    public static function getLengthCode(): array;
}
