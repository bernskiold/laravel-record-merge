<?php

namespace Bernskiold\LaravelRecordMerge\Tests\Models;

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model implements Mergeable
{
    use SupportsMerging;

    protected $guarded = [];

    public function tags()
    {
        return $this->belongsToMany(Tag::class)
            ->withPivot(['priority', 'notes'])
            ->withTimestamps();
    }

    public function morphTags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['priority', 'notes'])
            ->withTimestamps();
    }
}

