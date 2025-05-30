<?php

use Bernskiold\LaravelRecordMerge\Data\MergeMapConfig;

it('can be instantiated', function () {
    $config = new MergeMapConfig();
    expect($config)->toBeInstanceOf(MergeMapConfig::class);
});

it('can be instantiated with a map', function () {
    $map = [
        'name' => MergeMapConfig::SOURCE,
        'email' => MergeMapConfig::TARGET,
        'phone' => MergeMapConfig::SKIP,
    ];

    $config = new MergeMapConfig($map);
    expect($config->getMap())->toBe($map);
});

it('can be instantiated using the static make method', function () {
    $map = [
        'name' => MergeMapConfig::SOURCE,
        'email' => MergeMapConfig::TARGET,
        'phone' => MergeMapConfig::SKIP,
    ];

    $config = MergeMapConfig::make($map);
    expect($config->getMap())->toBe($map);
});

it('can check if the map is empty', function () {
    $emptyConfig = new MergeMapConfig();
    expect($emptyConfig->isEmpty())->toBeTrue();

    $nonEmptyConfig = new MergeMapConfig(['name' => MergeMapConfig::SOURCE]);
    expect($nonEmptyConfig->isEmpty())->toBeFalse();
});

it('can get the strategy for an attribute', function () {
    $config = new MergeMapConfig([
        'name' => MergeMapConfig::SOURCE,
        'email' => MergeMapConfig::TARGET,
        'phone' => MergeMapConfig::SKIP,
    ]);

    expect($config->getStrategyForAttribute('name'))->toBe(MergeMapConfig::SOURCE)
        ->and($config->getStrategyForAttribute('email'))->toBe(MergeMapConfig::TARGET)
        ->and($config->getStrategyForAttribute('phone'))->toBe(MergeMapConfig::SKIP)
        ->and($config->getStrategyForAttribute('unknown'))->toBeNull();
});

it('can check if an attribute should be merged from source', function () {
    $config = new MergeMapConfig([
        'name' => MergeMapConfig::SOURCE,
        'email' => MergeMapConfig::TARGET,
    ]);

    expect($config->shouldMergeFromSource('name'))->toBeTrue()
        ->and($config->shouldMergeFromSource('email'))->toBeFalse()
        ->and($config->shouldMergeFromSource('unknown'))->toBeFalse();
});

it('can check if an attribute should be kept on target', function () {
    $config = new MergeMapConfig([
        'name' => MergeMapConfig::SOURCE,
        'email' => MergeMapConfig::TARGET,
    ]);

    expect($config->shouldKeepOnTarget('email'))->toBeTrue()
        ->and($config->shouldKeepOnTarget('name'))->toBeFalse()
        ->and($config->shouldKeepOnTarget('unknown'))->toBeFalse();
});

it('can check if an attribute should be skipped', function () {
    $config = new MergeMapConfig([
        'name' => MergeMapConfig::SOURCE,
        'phone' => MergeMapConfig::SKIP,
    ]);

    expect($config->shouldSkip('phone'))->toBeTrue()
        ->and($config->shouldSkip('name'))->toBeFalse()
        ->and($config->shouldSkip('unknown'))->toBeFalse();
});

