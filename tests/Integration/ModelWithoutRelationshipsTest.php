<?php

use Bernskiold\LaravelRecordMerge\Tests\Models\ModelWithoutRelationships;
use Bernskiold\LaravelRecordMerge\Tests\Models\SoftDeletableModelWithoutRelationships;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('can be merged', function () {
    $modelA = ModelWithoutRelationships::create(['name' => 'Model A', 'description' => 'This is model A']);
    $modelB = ModelWithoutRelationships::create(['name' => 'Model B', 'description' => 'This is model B']);

    $modelA->mergeTo($modelB);

    assertDatabaseMissing('model_without_relationships', [
        'id' => $modelA->id,
        'name' => 'Model A',
        'description' => 'This is model A',
    ]);
});

it('trashes the record when soft deletable', function () {
    $modelA = SoftDeletableModelWithoutRelationships::create(['name' => 'Model A', 'description' => 'This is model A']);
    $modelB = SoftDeletableModelWithoutRelationships::create(['name' => 'Model B', 'description' => 'This is model B']);

    $modelA->mergeTo($modelB);

    assertDatabaseHas('soft_deletable_model_without_relationships', [
        'id' => $modelA->id,
    ]);

    $modelA->refresh();

    expect($modelA->trashed())->toBeTrue();
});

