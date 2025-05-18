<?php

use Bernskiold\LaravelRecordMerge\RecordMerge;
use Bernskiold\LaravelRecordMerge\Tests\Models\Tag;
use Bernskiold\LaravelRecordMerge\Tests\Models\TestModel;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Create source and target models
    $this->source = TestModel::create(['name' => 'Source Model']);
    $this->target = TestModel::create(['name' => 'Target Model']);
    
    // Create tags
    $this->tag1 = Tag::create(['name' => 'Morph Tag 1']);
    $this->tag2 = Tag::create(['name' => 'Morph Tag 2']);
    $this->tag3 = Tag::create(['name' => 'Morph Tag 3']);
    $this->tag4 = Tag::create(['name' => 'Morph Tag 4']);
    
    // Attach tags to source with pivot data
    $this->source->morphTags()->attach([
        $this->tag1->id => ['priority' => 1, 'notes' => 'Important morph tag'],
        $this->tag2->id => ['priority' => 2, 'notes' => 'Secondary morph tag'],
    ]);
    
    // Attach one tag to target (to test duplicate handling)
    $this->target->morphTags()->attach([
        $this->tag3->id => ['priority' => 3, 'notes' => 'Target morph tag'],
    ]);
});

test('it merges morph to many relationships', function () {
    // Perform the merge
    RecordMerge::new($this->source, $this->target)->merge();
    
    // Refresh the target model
    $this->target->refresh();
    
    // Assert that the target now has all the tags
    expect($this->target->morphTags)->toHaveCount(3)
        ->and($this->target->morphTags->pluck('id')->toArray())->toContain($this->tag1->id)
        ->and($this->target->morphTags->pluck('id')->toArray())->toContain($this->tag2->id)
        ->and($this->target->morphTags->pluck('id')->toArray())->toContain($this->tag3->id);
});

test('it preserves pivot data when merging morph to many relationships', function () {
    // Perform the merge
    RecordMerge::new($this->source, $this->target)->merge();
    
    // Refresh the target model
    $this->target->refresh();
    
    // Get the tag with pivot data
    $tag1WithPivot = $this->target->morphTags->where('id', $this->tag1->id)->first();
    $tag2WithPivot = $this->target->morphTags->where('id', $this->tag2->id)->first();
    $tag3WithPivot = $this->target->morphTags->where('id', $this->tag3->id)->first();
    
    // Assert that the pivot data was preserved
    expect($tag1WithPivot->pivot->priority)->toBe(1)
        ->and($tag1WithPivot->pivot->notes)->toBe('Important morph tag')
        ->and($tag2WithPivot->pivot->priority)->toBe(2)
        ->and($tag2WithPivot->pivot->notes)->toBe('Secondary morph tag')
        ->and($tag3WithPivot->pivot->priority)->toBe(3)
        ->and($tag3WithPivot->pivot->notes)->toBe('Target morph tag');
});

test('it does not duplicate existing morph relationships', function () {
    // Attach the same tag to both source and target with different pivot data
    $this->source->morphTags()->attach([
        $this->tag3->id => ['priority' => 5, 'notes' => 'Duplicate morph tag on source'],
    ]);
    
    // Perform the merge
    RecordMerge::new($this->source, $this->target)->merge();
    
    // Refresh the target model
    $this->target->refresh();
    
    // Assert that the target still has only 3 tags (no duplicates)
    expect($this->target->morphTags)->toHaveCount(3);
    
    // Get the tag3 with pivot data
    $tag3WithPivot = $this->target->morphTags->where('id', $this->tag3->id)->first();
    
    // Assert that the original pivot data on the target was preserved
    expect($tag3WithPivot->pivot->priority)->toBe(3)
        ->and($tag3WithPivot->pivot->notes)->toBe('Target morph tag');
});

test('it handles empty morph relationships', function () {
    // Create a new source with no relationships
    $emptySource = TestModel::create(['name' => 'Empty Morph Source']);
    
    // Perform the merge
    RecordMerge::new($emptySource, $this->target)->merge();
    
    // Refresh the target model
    $this->target->refresh();
    
    // Assert that the target still has its original tag
    expect($this->target->morphTags)->toHaveCount(1)
        ->and($this->target->morphTags->pluck('id')->toArray())->toContain($this->tag3->id);
});

test('it handles merging to an empty target with morph relationships', function () {
    // Create a new target with no relationships
    $emptyTarget = TestModel::create(['name' => 'Empty Morph Target']);
    
    // Perform the merge
    RecordMerge::new($this->source, $emptyTarget)->merge();
    
    // Refresh the empty target model
    $emptyTarget->refresh();
    
    // Assert that the empty target now has the source's tags
    expect($emptyTarget->morphTags)->toHaveCount(2)
        ->and($emptyTarget->morphTags->pluck('id')->toArray())->toContain($this->tag1->id)
        ->and($emptyTarget->morphTags->pluck('id')->toArray())->toContain($this->tag2->id);
});

test('it correctly handles the morph type field', function () {
    // Perform the merge
    RecordMerge::new($this->source, $this->target)->merge();
    
    // Check the database directly to verify the morph type field
    $morphEntries = DB::table('taggables')
        ->where('tag_id', $this->tag1->id)
        ->where('taggable_id', $this->target->id)
        ->get();
    
    expect($morphEntries)->toHaveCount(1)
        ->and($morphEntries->first()->taggable_type)->toBe(TestModel::class);
});
