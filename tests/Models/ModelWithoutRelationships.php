<?php

namespace Bernskiold\LaravelRecordMerge\Tests\Models;

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Illuminate\Database\Eloquent\Model;

class ModelWithoutRelationships extends Model implements Mergeable
{
    use SupportsMerging;

    protected $guarded = [];
}
