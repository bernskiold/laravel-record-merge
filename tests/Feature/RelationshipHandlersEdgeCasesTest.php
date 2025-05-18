<?php

use Bernskiold\LaravelRecordMerge\Exceptions\RelationshipHandlerException;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use Bernskiold\LaravelRecordMerge\Tests\Models\Tag;
use Bernskiold\LaravelRecordMerge\Tests\Models\TestModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Mockery;

afterEach(function () {
    Mockery::close();
});

test('it handles large numbers of relationships', function () {
    // Create source and target models
    $source = TestModel::create(['name' => 'Source Model']);
    $target = TestModel::create(['name' => 'Target Model']);
    
    // Create a large number of tags
    $tags = [];
    for ($i = 1; $i <= 50; $i++) {
        $tags[] = Tag::create(['name' => "Tag $i"]);
    }
    
    // Attach half the tags to the source model
    foreach (array_slice($tags, 0, 25) as $tag) {
        $source->tags()->attach($tag->id, [
            'priority' => rand(1, 10),
            'notes' => "Tag {$tag->id} notes",
        ]);
    }
    
    // Attach some different tags to the target model
    foreach (array_slice($tags, 25, 15) as $tag) {
        $target->tags()->attach($tag->id, [
            'priority' => rand(1, 10),
            'notes' => "Target tag {$tag->id} notes",
        ]);
    }
    
    // Perform the merge
    RecordMerge::new($source, $target)->merge();
    
    // Refresh the target model
    $target->refresh();
    
    // Assert that the target now has all the tags (25 from source + 15 from target = 40)
    expect($target->tags)->toHaveCount(40);
});

test('it handles custom pivot table names', function () {
    // Mock a custom BelongsToMany relation with a non-standard pivot table name
    $source = TestModel::create(['name' => 'Source Model']);
    $target = TestModel::create(['name' => 'Target Model']);
    $tag = Tag::create(['name' => 'Custom Pivot Tag']);
    
    // Create a mock BelongsToMany relation with a custom pivot table
    $mockRelation = Mockery::mock(BelongsToMany::class);
    $mockRelation->shouldReceive('getTable')->andReturn('custom_pivot_table');
    $mockRelation->shouldReceive('getForeignPivotKeyName')->andReturn('test_model_id');
    $mockRelation->shouldReceive('getRelatedPivotKeyName')->andReturn('tag_id');
    $mockRelation->shouldReceive('getPivotColumns')->andReturn(['test_model_id', 'tag_id', 'custom_field']);
    $mockRelation->shouldReceive('withPivot')->andReturn($mockRelation);
    $mockRelation->shouldReceive('get')->andReturn(collect([$tag]));
    $mockRelation->shouldReceive('detach')->once();
    
    // Mock the target relation
    $targetRelation = Mockery::mock(BelongsToMany::class);
    $targetRelation->shouldReceive('pluck')->andReturn(collect([]));
    $targetRelation->shouldReceive('attach')->once()->with($tag->id, Mockery::any());
    
    // Replace the actual relations with our mocks
    $source->shouldReceive('customRelation')->andReturn($mockRelation);
    $target->shouldReceive('customRelation')->andReturn($targetRelation);
    
    // Create a handler instance and call it directly
    $handler = new \Bernskiold\LaravelRecordMerge\RelationshipHandlers\BelongsToManyHandler();
    $handler->handle($source, $target, 'customRelation');
    
    // If we got here without exceptions, the test passes
    expect(true)->toBeTrue();
});

test('it handles custom morph type field names', function () {
    // Mock a custom MorphToMany relation with a non-standard morph type field
    $source = TestModel::create(['name' => 'Source Model']);
    $target = TestModel::create(['name' => 'Target Model']);
    $tag = Tag::create(['name' => 'Custom Morph Tag']);
    
    // Create a mock MorphToMany relation with a custom morph type field
    $mockRelation = Mockery::mock(MorphToMany::class);
    $mockRelation->shouldReceive('getTable')->andReturn('custom_morphs');
    $mockRelation->shouldReceive('getMorphType')->andReturn('custom_type');
    $mockRelation->shouldReceive('getForeignPivotKeyName')->andReturn('taggable_id');
    $mockRelation->shouldReceive('getRelatedPivotKeyName')->andReturn('tag_id');
    $mockRelation->shouldReceive('getPivotColumns')->andReturn(['taggable_id', 'tag_id', 'custom_type', 'custom_field']);
    $mockRelation->shouldReceive('withPivot')->andReturn($mockRelation);
    $mockRelation->shouldReceive('get')->andReturn(collect([$tag]));
    $mockRelation->shouldReceive('detach')->once();
    
    // Mock the target relation
    $targetRelation = Mockery::mock(MorphToMany::class);
    $targetRelation->shouldReceive('pluck')->andReturn(collect([]));
    $targetRelation->shouldReceive('attach')->once()->with($tag->id, Mockery::any());
    
    // Replace the actual relations with our mocks
    $source->shouldReceive('customMorphRelation')->andReturn($mockRelation);
    $target->shouldReceive('customMorphRelation')->andReturn($targetRelation);
    
    // Create a handler instance and call it directly
    $handler = new \Bernskiold\LaravelRecordMerge\RelationshipHandlers\MorphToManyHandler();
    $handler->handle($source, $target, 'customMorphRelation');
    
    // If we got here without exceptions, the test passes
    expect(true)->toBeTrue();
});
