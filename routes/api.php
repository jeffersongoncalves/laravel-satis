<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LaravelSatis\Controllers\DownloadComposerController;
use JeffersonGoncalves\LaravelSatis\Controllers\GithubWebhookController;

$prefix = config('satis.routes.api_prefix', 'api/satis');
$middleware = config('satis.routes.middleware', ['api']);

$composerPrefix = config('satis.tenancy.enabled')
    ? '{tenant}/composer/downloads'
    : 'composer/downloads';

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () use ($composerPrefix) {
        Route::post($composerPrefix, DownloadComposerController::class)
            ->name('composer.downloads');

        Route::post('webhooks/github/{package:reference}', [GithubWebhookController::class, 'handle'])
            ->name('webhooks.github');
    });
