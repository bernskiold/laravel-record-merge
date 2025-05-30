<?php

namespace Bernskiold\LaravelRecordMerge\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $guarded = [];

    public function testModels()
    {
        return $this->belongsToMany(TestModel::class);
    }

    public function morphTestModels()
    {
        return $this->morphedByMany(TestModel::class, 'taggable');
    }
}
