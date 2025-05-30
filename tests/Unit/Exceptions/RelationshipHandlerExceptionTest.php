<?php

use Bernskiold\LaravelRecordMerge\Exceptions\RelationshipHandlerException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('missing handler exception', function () {
    $relation = mock(BelongsTo::class);
    $exception = RelationshipHandlerException::missing($relation);

    expect($exception)
        ->toBeInstanceOf(RelationshipHandlerException::class)
        ->and($exception->getMessage())
        ->toBe('A relationship handler for '.get_class($relation).' could not be found.');
});
