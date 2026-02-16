<?php

namespace JeffersonGoncalves\LaravelSatis\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\LaravelSatis\LaravelSatisServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelSatisServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $configPath = __DIR__.'/../config/laravel-satis.php';
        if (file_exists($configPath)) {
            $app['config']->set('laravel-satis', require $configPath);
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        $migrationsPath = __DIR__.'/../database/migrations';

        foreach (glob($migrationsPath.'/*.php.stub') as $stub) {
            $migrationPath = str_replace('.php.stub', '.php', $stub);

            if (! file_exists($migrationPath)) {
                copy($stub, $migrationPath);
            }
        }

        $this->loadMigrationsFrom($migrationsPath);
    }
}
