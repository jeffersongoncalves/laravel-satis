<?php

use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelSatis\Controllers\ArchivesController;
use JeffersonGoncalves\LaravelSatis\Controllers\IncludeController;
use JeffersonGoncalves\LaravelSatis\Controllers\PackagesController;
use JeffersonGoncalves\LaravelSatis\Controllers\PackagesV2Controller;
use JeffersonGoncalves\LaravelSatis\Middleware\EnsureUserHasLicense;

$prefix = config('satis.routes.composer_prefix');
$guard = config('satis.auth.guard');

if (config('satis.tenancy.enabled')) {
    $prefix = ($prefix ? $prefix.'/' : '').'{tenant}';
}

Route::prefix($prefix ?? '')
    ->middleware([AuthenticateWithBasicAuth::using($guard), EnsureUserHasLicense::class])
    ->group(function () {
        Route::get('packages.json', PackagesController::class)->withoutMiddleware(EnsureUserHasLicense::class);
        Route::get('include/{include}.json', IncludeController::class)->withoutMiddleware(EnsureUserHasLicense::class);
        Route::get('p2/{vendor}/{package}.json', PackagesV2Controller::class);
        Route::get('archives/{vendor}/{package}/{file}', ArchivesController::class)->where('file', '.*\.zip');
    });
