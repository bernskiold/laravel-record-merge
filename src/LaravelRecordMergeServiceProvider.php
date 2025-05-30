<?php

namespace Bernskiold\LaravelRecordMerge;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

use function config;

class LaravelRecordMergeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AboutCommand::add('Laravel Record Merge', fn() => ['Version' => '1.0.0']);

        $this->publishes([
            __DIR__ . '/../config/record-merge.php' => config_path('record-merge.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/record-merge.php', 'record-merge'
        );
    }
}
