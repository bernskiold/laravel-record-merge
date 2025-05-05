<?php

namespace Bernskiold\LaravelRecordMerge\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
interface Mergeable
{

    public function getNotMergeableAttributes(): array;

}