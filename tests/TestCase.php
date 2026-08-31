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
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $this->testing_connection());

        $configPath = __DIR__.'/../config/satis.php';
        if (file_exists($configPath)) {
            $app['config']->set('satis', require $configPath);
        }
    }

    /**
     * Defaults to an in-memory SQLite connection for local development; CI
     * (tests.yml) sets SATIS_TEST_DB_* to run the same suite against real
     * MySQL and PostgreSQL instances too. Deliberately not the plain DB_*
     * names: Orchestra Testbench itself sets DB_CONNECTION=testing by
     * convention, which would collide with (and always win over) a driver
     * value read from the same variable here.
     *
     * @return array<string, mixed>
     */
    protected function testing_connection(): array
    {
        $driver = env('SATIS_TEST_DB_DRIVER', 'sqlite');

        if ($driver === 'sqlite') {
            return ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        }

        return [
            'driver' => $driver,
            'host' => env('SATIS_TEST_DB_HOST', '127.0.0.1'),
            'port' => env('SATIS_TEST_DB_PORT'),
            'database' => env('SATIS_TEST_DB_DATABASE', 'testing'),
            'username' => env('SATIS_TEST_DB_USERNAME', 'root'),
            'password' => env('SATIS_TEST_DB_PASSWORD', ''),
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'prefix' => '',
        ];
    }

    /**
     * Same order as LaravelSatisServiceProvider::hasMigrations(). SQLite
     * doesn't enforce foreign keys at CREATE TABLE time, but MySQL/Postgres
     * do, and alphabetical order breaks it here: e.g. "satis_package_token"
     * and "satis_package_releases" both sort before "satis_packages" they
     * reference ('_'/'r' < 's' in ASCII).
     */
    private const MIGRATION_ORDER = [
        'create_satis_credentials_table',
        'create_satis_packages_table',
        'create_satis_tokens_table',
        'create_satis_package_token_table',
        'create_satis_package_releases_table',
        'create_satis_dependencies_table',
        'create_satis_dependency_package_release_table',
        'create_satis_package_downloads_table',
        'create_satis_packagists_table',
    ];

    protected function defineDatabaseMigrations(): void
    {
        $stubsPath = __DIR__.'/../database/migrations';
        $tempPath = sys_get_temp_dir().'/laravel-satis-migrations';

        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        foreach (self::MIGRATION_ORDER as $index => $name) {
            copy($stubsPath.'/'.$name.'.php.stub', $tempPath.'/'.sprintf('%03d_%s.php', $index, $name));
        }

        // Test-only tenancy columns (HasTenancyTest): added as a real
        // migration so migrate:fresh always includes them, rather than an
        // ALTER TABLE inside the per-test transaction - which risked being
        // rolled back along with everything else and never reliably
        // recreated once RefreshDatabase's shared "already migrated" state
        // was set.
        $tenancyFixture = __DIR__.'/Fixtures/add_team_id_to_satis_tables.php.stub';
        $tenancyTarget = $tempPath.'/999_add_team_id_to_satis_tables.php';
        if (file_exists($tenancyFixture) && ! file_exists($tenancyTarget)) {
            copy($tenancyFixture, $tenancyTarget);
        }

        $this->loadMigrationsFrom($tempPath);
    }
}
