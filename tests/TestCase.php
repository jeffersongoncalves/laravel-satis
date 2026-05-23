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

        $configPath = __DIR__.'/../config/satis.php';
        if (file_exists($configPath)) {
            $app['config']->set('satis', require $configPath);
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        $stubsPath = __DIR__.'/../database/migrations';
        $tempPath = sys_get_temp_dir().'/laravel-satis-migrations';

        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        foreach (glob($stubsPath.'/*.php.stub') as $stub) {
            $filename = basename(str_replace('.php.stub', '.php', $stub));
            $target = $tempPath.'/'.$filename;

            copy($stub, $target);
        }

        $this->loadMigrationsFrom($tempPath);
    }
}
