<?php

namespace JeffersonGoncalves\LaravelSatis;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use JeffersonGoncalves\LaravelSatis\Commands\DependencyPackages;
use JeffersonGoncalves\LaravelSatis\Commands\SatisBuild;
use JeffersonGoncalves\LaravelSatis\Commands\SatisClean;
use JeffersonGoncalves\LaravelSatis\Commands\SatisSanitize;
use JeffersonGoncalves\LaravelSatis\Commands\SatisTokenBuild;
use JeffersonGoncalves\LaravelSatis\Commands\SatisValidate;
use JeffersonGoncalves\LaravelSatis\Observers\DependencyObserver;
use JeffersonGoncalves\LaravelSatis\Observers\PackageDownloadObserver;
use JeffersonGoncalves\LaravelSatis\Observers\PackageObserver;
use JeffersonGoncalves\LaravelSatis\Observers\PackageReleaseObserver;
use JeffersonGoncalves\LaravelSatis\Observers\TokenObserver;
use JeffersonGoncalves\LaravelSatis\Providers\EloquentTokenProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSatisServiceProvider extends PackageServiceProvider
{
    public static string $name = 'laravel-satis';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_satis_packages_table',
                'create_satis_tokens_table',
                'create_satis_package_token_table',
                'create_satis_package_releases_table',
                'create_satis_dependencies_table',
                'create_satis_dependency_package_release_table',
                'create_satis_package_downloads_table',
                'create_satis_packagists_table',
            ])
            ->hasRoutes(['api', 'composer'])
            ->hasCommands([
                SatisBuild::class,
                SatisClean::class,
                SatisSanitize::class,
                SatisTokenBuild::class,
                SatisValidate::class,
                DependencyPackages::class,
            ]);
    }

    public function packageBooted(): void
    {
        $this->registerAuthProvider();
        $this->registerObservers();
        $this->registerSchedule();
    }

    protected function registerAuthProvider(): void
    {
        if (! config('satis.auth')) {
            return;
        }

        Auth::provider(
            config('satis.auth.provider'),
            fn ($app, $config) => new EloquentTokenProvider(
                $app['hash'],
                config('satis.models.token')
            )
        );

        config([
            'auth.guards.'.config('satis.auth.guard') => [
                'driver' => 'session',
                'provider' => config('satis.auth.provider'),
            ],
            'auth.providers.'.config('satis.auth.provider') => [
                'driver' => config('satis.auth.provider'),
                'model' => config('satis.models.token'),
            ],
        ]);
    }

    protected function registerObservers(): void
    {
        $models = config('satis.models');

        if (! $models) {
            return;
        }

        $models['package']::observe(PackageObserver::class);
        $models['token']::observe(TokenObserver::class);
        $models['dependency']::observe(DependencyObserver::class);
        $models['package_download']::observe(PackageDownloadObserver::class);
        $models['package_release']::observe(PackageReleaseObserver::class);
    }

    protected function registerSchedule(): void
    {
        $schedule = config('satis.schedule');

        if (! $schedule) {
            return;
        }

        $this->app->booted(function () use ($schedule) {
            $s = $this->app->make(Schedule::class);

            if ($schedule['build'] ?? null) {
                $s->command('satis:build')->{$schedule['build']}();
            }

            if ($schedule['token_build'] ?? null) {
                $s->command('satis:token-build')->{$schedule['token_build']}();
            }

            if ($schedule['validate'] ?? null) {
                $s->command('satis:validate')->{$schedule['validate']}();
            }

            if ($schedule['sanitize'] ?? null) {
                $s->command('satis:sanitize')->{$schedule['sanitize']}();
            }

            if ($schedule['dependencies'] ?? null) {
                $s->command('dependency:packages')->{$schedule['dependencies']}();
            }
        });
    }
}
