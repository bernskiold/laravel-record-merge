<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeData;

it('can be constructed', function () {
    $model = new class implements Mergeable {
        use SupportsMerging;
    };

    $sourceModel = clone $model;
    $targetModel = clone $model;

    expect(new MergeData($sourceModel, $targetModel, [], []))
        ->toBeInstanceOf(MergeData::class)
        ->source->toBe($sourceModel)
        ->target->toBe($targetModel)
        ->relationshipCounts->toBeArray()
        ->comparison->toBeArray();
});
