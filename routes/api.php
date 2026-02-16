<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelSatis\Controllers\DownloadComposerController;
use JeffersonGoncalves\LaravelSatis\Controllers\GithubWebhookController;
use JeffersonGoncalves\LaravelSatis\Middleware\EnsureUserHasLicense;

$prefix = config('laravel-satis.routes.api_prefix', 'api/satis');
$middleware = config('laravel-satis.routes.middleware', ['api']);

$composerPrefix = config('laravel-satis.tenancy.enabled')
    ? '{tenant}/composer/downloads'
    : 'composer/downloads';

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () use ($composerPrefix) {
        Route::post($composerPrefix, [DownloadComposerController::class, 'store'])
            ->middleware(EnsureUserHasLicense::class);

        Route::post('webhooks/github/{package:reference}', [GithubWebhookController::class, 'handle']);
    });
