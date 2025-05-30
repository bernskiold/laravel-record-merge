<?php

namespace Bernskiold\LaravelRecordMerge\Tests\Models;

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class TestModel extends Model implements Mergeable
{
    use SupportsMerging;

    protected $guarded = [];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withPivot(['priority', 'notes'])
            ->withTimestamps();
    }

    public function morphTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['priority', 'notes'])
            ->withTimestamps();
    }

    // HasMany relationship for testing HasManyHandler
    public function children(): HasMany
    {
        return $this->hasMany(TestModel::class, 'parent_id');
    }

    // BelongsTo relationship for the children
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TestModel::class, 'parent_id');
    }

    // HasOne relationship for testing HasOneHandler
    public function profile(): HasOne
    {
        return $this->hasOne(TestModel::class, 'profile_parent_id');
    }

    // BelongsTo relationship for the profile
    public function profileParent(): BelongsTo
    {
        return $this->belongsTo(TestModel::class, 'profile_parent_id');
    }

    // MorphMany relationship for testing MorphManyHandler
    public function comments(): MorphMany
    {
        return $this->morphMany(TestModel::class, 'commentable');
    }
}
