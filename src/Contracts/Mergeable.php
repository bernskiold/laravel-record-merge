<?php

namespace Bernskiold\LaravelRecordMerge\Contracts;

use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;

/**
 * @mixin Model
 */
interface Mergeable
{

    /**
     * Handle the merging of this record with another record of the same type.
     * Because merging can be a complex and time-consuming operation,
     * this method be run as a job.
     */
    public function mergeTo(Mergeable $target): PendingDispatch|PendingClosureDispatch;

    /**
     * Preview the merge of this record with another record of the same type.
     * This lets you see how the merge would affect the target record
     * and any relationships before actually performing the merge.
     */
    public function previewMergeTo(Mergeable $target): MergeData;

    /**
     * Attributes that should not be merged.
     * This method should return an array of attribute names that.
     */
    public function getNotMergeableAttributes(): array;

    /**
     * Get the label for the mergeable record.
     *
     * This label can be used in the UI to reference the record
     * as well as in logs. This can be helpful to identify
     * records that were later removed.
     */
    public function getMergeableLabel(): ?string;

}
