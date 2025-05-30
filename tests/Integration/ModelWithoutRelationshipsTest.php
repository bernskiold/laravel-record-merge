<?php

use Bernskiold\LaravelRecordMerge\Tests\Models\ModelWithoutRelationships;
use Bernskiold\LaravelRecordMerge\Tests\Models\SoftDeletableModelWithoutRelationships;
use function Pest\Laravel\assertDatabaseMissing;

it('can be merged', function () {
    $modelA = ModelWithoutRelationships::create(['name' => 'Model A']);
    $modelB = ModelWithoutRelationships::create(['name' => 'Model B']);

    $modelA->mergeTo($modelB);

    assertDatabaseMissing('model_without_relationships', [
        'id' => $modelA->id,
    ]);

    expect($modelB->refresh())->name->toBe('Model B');
});

it('fills in missing target attributes with source attributes', function () {
    $modelA = ModelWithoutRelationships::create(['name' => 'Model A', 'description' => 'This is model A']);
    $modelB = ModelWithoutRelationships::create(['name' => 'Model B']);

    $modelA->mergeTo($modelB);

    expect($modelB->refresh())
        ->name->toBe('Model B')
        ->description->toBe('This is model A')
        ->created_at->toEqual($modelB->created_at)
        ->updated_at->toEqual($modelB->updated_at);
});

it('trashes the record when soft deletable', function () {
    $modelA = SoftDeletableModelWithoutRelationships::create(['name' => 'Model A']);
    $modelB = SoftDeletableModelWithoutRelationships::create(['name' => 'Model B']);

    $modelA->mergeTo($modelB);

    expect($modelA->refresh())
        ->trashed()->toBeTrue();
});
