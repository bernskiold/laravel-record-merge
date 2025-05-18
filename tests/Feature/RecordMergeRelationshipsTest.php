<?php

use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use Bernskiold\LaravelRecordMerge\Tests\Models\Tag;
use Bernskiold\LaravelRecordMerge\Tests\Models\TestModel;

beforeEach(function () {
    // Create source and target models
    $this->source = TestModel::create(['name' => 'Source Model']);
    $this->target = TestModel::create(['name' => 'Target Model']);
    
    // Create tags
    $this->tag1 = Tag::create(['name' => 'Tag 1']);
    $this->tag2 = Tag::create(['name' => 'Tag 2']);
    $this->tag3 = Tag::create(['name' => 'Tag 3']);
    
    // Attach tags to source with pivot data
    $this->source->tags()->attach([
        $this->tag1->id => ['priority' => 1, 'notes' => 'Regular tag'],
    ]);
    
    $this->source->morphTags()->attach([
        $this->tag2->id => ['priority' => 2, 'notes' => 'Morph tag'],
    ]);
    
    // Attach one tag to target (to test duplicate handling)
    $this->target->tags()->attach([
        $this->tag3->id => ['priority' => 3, 'notes' => 'Target tag'],
    ]);
});

test('it correctly identifies relationships in preview', function () {
    // Get the preview data
    $preview = RecordMerge::new($this->source, $this->target)->preview();
    
    // Assert that the preview contains the correct relationship counts
    expect($preview)->toBeInstanceOf(MergeData::class)
        ->and($preview->relationshipCounts)->toHaveKey('tags')
        ->and($preview->relationshipCounts)->toHaveKey('morphTags')
        ->and($preview->relationshipCounts['tags']->sourceCount)->toBe(1)
        ->and($preview->relationshipCounts['tags']->targetCount)->toBe(1)
        ->and($preview->relationshipCounts['morphTags']->sourceCount)->toBe(1)
        ->and($preview->relationshipCounts['morphTags']->targetCount)->toBe(0);
});

test('it merges both relationship types in a single operation', function () {
    // Perform the merge
    RecordMerge::new($this->source, $this->target)->merge();
    
    // Refresh the target model
    $this->target->refresh();
    
    // Assert that the target now has all the tags from both relationship types
    expect($this->target->tags)->toHaveCount(2)
        ->and($this->target->tags->pluck('id')->toArray())->toContain($this->tag1->id)
        ->and($this->target->tags->pluck('id')->toArray())->toContain($this->tag3->id)
        ->and($this->target->morphTags)->toHaveCount(1)
        ->and($this->target->morphTags->pluck('id')->toArray())->toContain($this->tag2->id);
    
    // Check pivot data
    $regularTag = $this->target->tags->where('id', $this->tag1->id)->first();
    $morphTag = $this->target->morphTags->where('id', $this->tag2->id)->first();
    
    expect($regularTag->pivot->priority)->toBe(1)
        ->and($regularTag->pivot->notes)->toBe('Regular tag')
        ->and($morphTag->pivot->priority)->toBe(2)
        ->and($morphTag->pivot->notes)->toBe('Morph tag');
});

test('it handles the afterMerging callback', function () {
    $callbackExecuted = false;
    
    // Perform the merge with a callback
    RecordMerge::new($this->source, $this->target)
        ->afterMerging(function ($source, $target) use (&$callbackExecuted) {
            $callbackExecuted = true;
            
            // Assert that the relationships were merged before the callback is executed
            expect($target->tags()->count())->toBe(2)
                ->and($target->morphTags()->count())->toBe(1);
        })
        ->merge();
    
    // Assert that the callback was executed
    expect($callbackExecuted)->toBeTrue();
});

