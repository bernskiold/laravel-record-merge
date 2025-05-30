<?php

namespace Bernskiold\LaravelRecordMerge\Contracts;

use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Illuminate\Contracts\Auth\Authenticatable;

interface MergeLogger
{

    public function log(Mergeable $source, Mergeable $target, ?MergeData $data = null, ?Authenticatable $performedBy = null): void;

}
