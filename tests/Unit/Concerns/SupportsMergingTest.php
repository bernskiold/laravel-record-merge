<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
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
