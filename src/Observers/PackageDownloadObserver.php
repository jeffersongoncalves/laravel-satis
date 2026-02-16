<?php

namespace JeffersonGoncalves\LaravelSatis\Observers;

use JeffersonGoncalves\LaravelSatis\Models\PackageDownload;

class PackageDownloadObserver
{
    public function created(PackageDownload $download): void
    {
        //
    }
}
