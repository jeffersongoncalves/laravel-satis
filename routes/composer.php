<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelSatis\Controllers\ArchivesController;
use JeffersonGoncalves\LaravelSatis\Controllers\IncludeController;
use JeffersonGoncalves\LaravelSatis\Controllers\PackagesController;
use JeffersonGoncalves\LaravelSatis\Controllers\PackagesV2Controller;
use JeffersonGoncalves\LaravelSatis\Middleware\EnsureUserHasLicense;

$prefix = config('satis.routes.composer_prefix', 'satis');
$middleware = config('satis.routes.middleware', ['api']);

if (config('satis.tenancy.enabled')) {
    $prefix .= '/{tenant}';
}

Route::prefix($prefix)
    ->middleware([...$middleware, EnsureUserHasLicense::class])
    ->group(function () {
        Route::get('packages.json', [PackagesController::class, 'index']);
        Route::get('include/{include}.json', [IncludeController::class, 'show']);
        Route::get('p2/{vendor}/{package}.json', [PackagesV2Controller::class, 'show']);
        Route::get('archives/{vendor}/{package}/{file}', [ArchivesController::class, 'show']);
    });
