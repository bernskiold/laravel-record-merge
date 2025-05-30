<?php

namespace Bernskiold\LaravelRecordMerge\Tests\Models;

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function morphTags(): BelongsTo
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['priority', 'notes'])
            ->withTimestamps();
    }
}

