<?php

namespace Bernskiold\LaravelRecordMerge\Tests;

use Bernskiold\LaravelRecordMerge\LaravelRecordMergeServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Bernskiold\\LaravelRecordMerge\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->artisan('migrate')->run();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelRecordMergeServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $driver = env('DB_DRIVER', 'sqlite');

        if ($driver === 'sqlite' && extension_loaded('pdo_sqlite')) {
            config()->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        } elseif ($driver === 'pgsql' || (! extension_loaded('pdo_sqlite') && extension_loaded('pdo_pgsql'))) {
            config()->set('database.connections.testing', [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'laravel_record_merge_test'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
            ]);
        } elseif ($driver === 'mysql' || (! extension_loaded('pdo_sqlite') && extension_loaded('pdo_mysql'))) {
            config()->set('database.connections.testing', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'laravel_record_merge_test'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);
        } else {
            // Fallback to SQLite (will fail if extension not available)
            config()->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }
    }
}
