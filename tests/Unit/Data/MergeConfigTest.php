<?php

use Bernskiold\LaravelRecordMerge\Data\MergeConfig;
use Bernskiold\LaravelRecordMerge\Enums\MergeStrategy;

it('can be instantiated', function () {
    $config = new MergeConfig();
    expect($config)->toBeInstanceOf(MergeConfig::class);
});

it('can be instantiated with a map', function () {
    $map = [
        'name' => MergeStrategy::UseSource,
        'email' => MergeStrategy::UseTarget,
        'phone' => MergeStrategy::Skip,
    ];

    $config = new MergeConfig($map);
    expect($config->getMap())->toBe($map);
});

it('can be instantiated using the static make method', function () {
    $map = [
        'name' => MergeStrategy::UseSource,
        'email' => MergeStrategy::UseTarget,
        'phone' => MergeStrategy::Skip,
    ];

    $config = MergeConfig::make($map);
    expect($config->getMap())->toBe($map);
});

it('can check if the map is empty', function () {
    $emptyConfig = new MergeConfig();
    expect($emptyConfig->isEmpty())->toBeTrue();

    $nonEmptyConfig = new MergeConfig(['name' => MergeStrategy::UseSource]);
    expect($nonEmptyConfig->isEmpty())->toBeFalse();
});

it('can get the strategy for an attribute', function () {
    $config = new MergeConfig([
        'name' => MergeStrategy::UseSource,
        'email' => MergeStrategy::UseTarget,
        'phone' => MergeStrategy::Skip,
    ]);

    expect($config->getStrategyForAttribute('name'))->toBe(MergeStrategy::UseSource)
        ->and($config->getStrategyForAttribute('email'))->toBe(MergeStrategy::UseTarget)
        ->and($config->getStrategyForAttribute('phone'))->toBe(MergeStrategy::Skip)
        ->and($config->getStrategyForAttribute('unknown'))->toBeNull();
});

it('can check if an attribute should be merged from source', function () {
    $config = new MergeConfig([
        'name' => MergeStrategy::UseSource,
        'email' => MergeStrategy::UseTarget,
    ]);

    expect($config->shouldMergeFromSource('name'))->toBeTrue()
        ->and($config->shouldMergeFromSource('email'))->toBeFalse()
        ->and($config->shouldMergeFromSource('unknown'))->toBeFalse();
});

it('can check if an attribute should be kept on target', function () {
    $config = new MergeConfig([
        'name' => MergeStrategy::UseSource,
        'email' => MergeStrategy::UseTarget,
    ]);

    expect($config->shouldKeepOnTarget('email'))->toBeTrue()
        ->and($config->shouldKeepOnTarget('name'))->toBeFalse()
        ->and($config->shouldKeepOnTarget('unknown'))->toBeFalse();
});

it('can check if an attribute should be skipped', function () {
    $config = new MergeConfig([
        'name' => MergeStrategy::UseSource,
        'phone' => MergeStrategy::Skip,
    ]);

    expect($config->shouldSkip('phone'))->toBeTrue()
        ->and($config->shouldSkip('name'))->toBeFalse()
        ->and($config->shouldSkip('unknown'))->toBeFalse();
});

