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
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
