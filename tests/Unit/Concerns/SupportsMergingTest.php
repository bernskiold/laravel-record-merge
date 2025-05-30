<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Bernskiold\LaravelRecordMerge\Data\MergeMapConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Bus;

it('can initiate a merge', function () {
    Bus::fake();

    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $sourceModel = clone $model;
    $sourceModel->id = 1;

    $targetModel = clone $model;
    $targetModel->id = 2;

    expect($sourceModel->mergeTo($targetModel))
        ->toBeInstanceOf(PendingDispatch::class);
});

it('can initiate a merge with a merge map', function () {
    Bus::fake();

    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $sourceModel = clone $model;
    $sourceModel->id = 1;

    $targetModel = clone $model;
    $targetModel->id = 2;

    $mergeMap = new MergeMapConfig([
        'name' => MergeMapConfig::SOURCE,
        'email' => MergeMapConfig::TARGET,
    ]);

    expect($sourceModel->mergeTo($targetModel, $mergeMap))
        ->toBeInstanceOf(PendingDispatch::class);
});

it('returns empty array with not mergeable attributes by default', function () {
    $model = new class implements Mergeable {
        use SupportsMerging;
    };

    expect($model->getNotMergeableAttributes())->toBeEmpty();
});

it('can perform a preview', function () {
    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $sourceModel = clone $model;
    $sourceModel->id = 1;

    $targetModel = clone $model;
    $targetModel->id = 2;

    expect($model->previewMergeTo($targetModel))
        ->toBeInstanceOf(MergeData::class);
});

it('can perform a preview with a merge map', function () {
    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $sourceModel = clone $model;
    $sourceModel->id = 1;

    $targetModel = clone $model;
    $targetModel->id = 2;

    $mergeMap = new MergeMapConfig([
        'name' => MergeMapConfig::SOURCE,
        'email' => MergeMapConfig::TARGET,
    ]);

    expect($model->previewMergeTo($targetModel, $mergeMap))
        ->toBeInstanceOf(MergeData::class);
});

