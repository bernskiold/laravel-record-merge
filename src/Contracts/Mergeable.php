<?php

namespace Bernskiold\LaravelRecordMerge\Contracts;

use Bernskiold\LaravelRecordMerge\Data\MergeConfig;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Collection;

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
    public function mergeTo(Mergeable $target, ?MergeConfig $mergeConfig = null): PendingDispatch|PendingClosureDispatch;

    /**
     * Preview the merge of this record with another record of the same type.
     * This lets you see how the merge would affect the target record
     * and any relationships before actually performing the merge.
     */
    public function previewMergeTo(Mergeable $target, ?MergeConfig $mergeConfig = null): MergeData;

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

    /**
     * Get the possible records for merging based on a search term.
     *
     * This method should return a collection of records that match the search term.
     * The amount parameter can be used to limit the number of results returned.
     * The query is designed to run on the source model and should
     * include the model ID in the search.
     */
    public function getPossibleRecordsForMerging(string $search, int $amount = 10): Collection;
}
