<?php

use Bernskiold\LaravelRecordMerge\Tests\Models\Tag;
use Bernskiold\LaravelRecordMerge\Tests\Models\TestModel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    // Create test models and tags for use in tests
    $this->sourceModel = TestModel::create(['name' => 'Source Model']);
    $this->targetModel = TestModel::create(['name' => 'Target Model']);

    // Create some tags
    $this->tag1 = Tag::create(['name' => 'Tag 1']);
    $this->tag2 = Tag::create(['name' => 'Tag 2']);
    $this->tag3 = Tag::create(['name' => 'Tag 3']);
});

it('handles BelongsToMany relationships correctly', function () {
    // Attach tags to source model with pivot data
    $this->sourceModel->tags()->attach($this->tag1->id, [
        'priority' => 10,
        'notes' => 'Source note 1',
    ]);
    $this->sourceModel->tags()->attach($this->tag2->id, [
        'priority' => 20,
        'notes' => 'Source note 2',
    ]);

    // Attach one tag to target model
    $this->targetModel->tags()->attach($this->tag3->id, [
        'priority' => 30,
        'notes' => 'Target note 3',
    ]);

    // Merge source to target
    $this->sourceModel->mergeTo($this->targetModel);

    // Source model should be deleted
    assertDatabaseMissing('test_models', [
        'id' => $this->sourceModel->id,
    ]);

    // Target model should have all tags
    $targetTags = $this->targetModel->refresh()->tags;

    // Should have 3 tags total
    expect($targetTags)->toHaveCount(3);

    // Check that pivot data was preserved
    $tag1Relation = $targetTags->firstWhere('id', $this->tag1->id);
    $tag2Relation = $targetTags->firstWhere('id', $this->tag2->id);
    $tag3Relation = $targetTags->firstWhere('id', $this->tag3->id);

    expect($tag1Relation->pivot->priority)->toBe(10);
    expect($tag1Relation->pivot->notes)->toBe('Source note 1');

    expect($tag2Relation->pivot->priority)->toBe(20);
    expect($tag2Relation->pivot->notes)->toBe('Source note 2');

    expect($tag3Relation->pivot->priority)->toBe(30);
    expect($tag3Relation->pivot->notes)->toBe('Target note 3');
});

it('handles MorphToMany relationships correctly', function () {
    // Attach tags to source model with pivot data
    $this->sourceModel->morphTags()->attach($this->tag1->id, [
        'priority' => 10,
        'notes' => 'Source morph note 1',
    ]);
    $this->sourceModel->morphTags()->attach($this->tag2->id, [
        'priority' => 20,
        'notes' => 'Source morph note 2',
    ]);

    // Attach one tag to target model
    $this->targetModel->morphTags()->attach($this->tag3->id, [
        'priority' => 30,
        'notes' => 'Target morph note 3',
    ]);

    // Merge source to target
    $this->sourceModel->mergeTo($this->targetModel);

    // Source model should be deleted
    assertDatabaseMissing('test_models', [
        'id' => $this->sourceModel->id,
    ]);

    // Target model should have all tags
    $targetTags = $this->targetModel->refresh()->morphTags;

    // Should have 3 tags total
    expect($targetTags)->toHaveCount(3);

    // Check that pivot data was preserved
    $tag1Relation = $targetTags->firstWhere('id', $this->tag1->id);
    $tag2Relation = $targetTags->firstWhere('id', $this->tag2->id);
    $tag3Relation = $targetTags->firstWhere('id', $this->tag3->id);

    expect($tag1Relation->pivot->priority)->toBe(10);
    expect($tag1Relation->pivot->notes)->toBe('Source morph note 1');

    expect($tag2Relation->pivot->priority)->toBe(20);
    expect($tag2Relation->pivot->notes)->toBe('Source morph note 2');

    expect($tag3Relation->pivot->priority)->toBe(30);
    expect($tag3Relation->pivot->notes)->toBe('Target morph note 3');
});

it('handles HasMany relationships correctly', function () {
    // Create parent models
    $sourceParent = TestModel::create(['name' => 'Source Parent']);
    $targetParent = TestModel::create(['name' => 'Target Parent']);

    // Create child models related to source parent
    $child1 = TestModel::create(['name' => 'Child 1', 'parent_id' => $sourceParent->id]);
    $child2 = TestModel::create(['name' => 'Child 2', 'parent_id' => $sourceParent->id]);

    // Create child model related to target parent
    $child3 = TestModel::create(['name' => 'Child 3', 'parent_id' => $targetParent->id]);

    // Merge source parent to target parent
    $sourceParent->mergeTo($targetParent);

    // Source parent should be deleted
    assertDatabaseMissing('test_models', [
        'id' => $sourceParent->id,
    ]);

    // All children should now be related to target parent
    assertDatabaseHas('test_models', [
        'id' => $child1->id,
        'parent_id' => $targetParent->id,
    ]);

    assertDatabaseHas('test_models', [
        'id' => $child2->id,
        'parent_id' => $targetParent->id,
    ]);

    assertDatabaseHas('test_models', [
        'id' => $child3->id,
        'parent_id' => $targetParent->id,
    ]);

    // Target parent should have all children
    $targetChildren = $targetParent->refresh()->children;
    expect($targetChildren)->toHaveCount(3);
});

it('handles HasOne relationships correctly', function () {
    // Create parent models
    $sourceParent = TestModel::create(['name' => 'Source Parent']);
    $targetParent = TestModel::create(['name' => 'Target Parent']);

    // Create profile for source parent
    $sourceProfile = TestModel::create(['name' => 'Source Profile', 'profile_parent_id' => $sourceParent->id]);

    // Merge source parent to target parent
    $sourceParent->mergeTo($targetParent);

    // Source parent should be deleted
    assertDatabaseMissing('test_models', [
        'id' => $sourceParent->id,
    ]);

    // Profile should now be related to target parent
    assertDatabaseHas('test_models', [
        'id' => $sourceProfile->id,
        'profile_parent_id' => $targetParent->id,
    ]);

    // Target parent should have the profile
    $targetProfile = $targetParent->refresh()->profile;
    expect($targetProfile->id)->toBe($sourceProfile->id);
});

it('handles MorphMany relationships correctly', function () {
    // Create parent models
    $sourceParent = TestModel::create(['name' => 'Source Parent']);
    $targetParent = TestModel::create(['name' => 'Target Parent']);

    // Create comments for source parent
    $comment1 = TestModel::create(['name' => 'Comment 1']);
    $comment1->commentable_id = $sourceParent->id;
    $comment1->commentable_type = get_class($sourceParent);
    $comment1->save();

    $comment2 = TestModel::create(['name' => 'Comment 2']);
    $comment2->commentable_id = $sourceParent->id;
    $comment2->commentable_type = get_class($sourceParent);
    $comment2->save();

    // Create comment for target parent
    $comment3 = TestModel::create(['name' => 'Comment 3']);
    $comment3->commentable_id = $targetParent->id;
    $comment3->commentable_type = get_class($targetParent);
    $comment3->save();

    // Merge source parent to target parent
    $sourceParent->mergeTo($targetParent);

    // Source parent should be deleted
    assertDatabaseMissing('test_models', [
        'id' => $sourceParent->id,
    ]);

    // All comments should now be related to target parent
    assertDatabaseHas('test_models', [
        'id' => $comment1->id,
        'commentable_id' => $targetParent->id,
        'commentable_type' => get_class($targetParent),
    ]);

    assertDatabaseHas('test_models', [
        'id' => $comment2->id,
        'commentable_id' => $targetParent->id,
        'commentable_type' => get_class($targetParent),
    ]);

    assertDatabaseHas('test_models', [
        'id' => $comment3->id,
        'commentable_id' => $targetParent->id,
        'commentable_type' => get_class($targetParent),
    ]);

    // Target parent should have all comments
    $targetComments = $targetParent->refresh()->comments;
    expect($targetComments)->toHaveCount(3);
});
