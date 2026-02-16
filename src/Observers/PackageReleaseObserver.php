<?php

namespace JeffersonGoncalves\LaravelSatis\Observers;

use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;

class PackageReleaseObserver
{
    public function created(PackageRelease $release): void
    {
        //
    }

    public function deleted(PackageRelease $release): void
    {
        //
    }
}
