<?php

namespace Bernskiold\LaravelRecordMerge\Contracts;

use Bernskiold\LaravelRecordMerge\Data\MergeData;

interface MergeLogger
{

    public function log(Mergeable $source, Mergeable $target, MergeData $data): void;

}