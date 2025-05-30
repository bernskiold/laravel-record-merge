<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Bernskiold\LaravelRecordMerge\Data\RelationshipCount;

it('can be constructed', function () {
    expect(new RelationshipCount(
        relationship: 'test_relationship',
        sourceCount: 5,
        targetCount: 10,
    ))
        ->toBeInstanceOf(RelationshipCount::class)
        ->relationship->toBe('test_relationship')
        ->sourceCount->toBe(5)
        ->targetCount->toBe(10);
});
