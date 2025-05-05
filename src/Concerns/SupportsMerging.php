<?php

namespace Bernskiold\LaravelRecordMerge\Concerns;

use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Bernskiold\LaravelRecordMerge\Jobs\MergeRecordJob;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use \Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;

trait SupportsMerging
{

    /**
     * Handle the merging of this record with
     * another record of the same type.
     */
    public function mergeTo(Mergeable $target): PendingDispatch|PendingClosureDispatch
    {
        /**
         * @var Mergeable $this
         */
        return dispatch(new MergeRecordJob($this, $target));
    }

    /**
     * Preview the merge of this record with
     * another record of the same type.
     *
     * The output is a MergeData object that contains
     * the details of how the merge would affect the
     * target record as well as any relationships.
     */
    public function previewMergeTo(Mergeable $target): MergeData
    {
        /**
         * @var Mergeable $this
         */

        return RecordMerge::new($this, $target)->preview();
    }

    /**
     * Update the records after merging.
     *
     * This method is called as part of the merge process and
     * can be overridden in the child class to perform
     * any actions after merging.
     *
     * For example, you might want to deprecate or delete
     * the source record after merging.
     */
    protected static function updateAfterMerging(Mergeable $source, Mergeable $target): void
    {
        //
    }
}