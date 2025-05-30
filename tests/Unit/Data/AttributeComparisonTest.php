<?php

use Bernskiold\LaravelRecordMerge\Data\AttributeComparison;

it('can be constructed with source and target values', function () {
    $sourceValue = 'source';
    $targetValue = 'target';

    $comparison = new AttributeComparison($sourceValue, $targetValue);

    expect($comparison->sourceValue)->toBe($sourceValue)
        ->and($comparison->targetValue)->toBe($targetValue);
});
