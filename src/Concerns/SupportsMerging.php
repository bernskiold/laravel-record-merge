<?php

namespace Bernskiold\LaravelRecordMerge\Concerns;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeConfig;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException;
use Bernskiold\LaravelRecordMerge\Jobs\MergeRecordJob;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Collection;
use function method_exists;

/**
 * Supports Merging
 *
 * This trait provides common functionality for the record merging
 * mechanism to fulfil the Mergeable contract.
 *
 * It should be used ion any Eloquent model that needs to support
 * merging with another record of the same type.
 *
 * It provides both a method to perform the merge and a method
 * to preview the merge before it is executed.
 *
 * @mixin Model
 *
 * @phpstan-ignore trait.unused
 */
trait SupportsMerging
{
    /**
     * Handle the merging of this record with
     * another record of the same type.
     */
    public function mergeTo(Mergeable $target, ?MergeConfig $mergeConfig = null): PendingDispatch|PendingClosureDispatch
    {
        return dispatch(new MergeRecordJob($this, $target, auth()->user(), $mergeConfig));
    }

    /**
     * Preview the merge of this record with
     * another record of the same type.
     *
     * The output is a MergeData object that contains
     * the details of how the merge would affect the
     * target record as well as any relationships.
     *
     * @throws InvalidRecordMergeException
     */
    public function previewMergeTo(Mergeable $target, ?MergeConfig $mergeConfig = null): MergeData
    {
        return RecordMerge::new($this, $target)
            ->withMergeConfig($mergeConfig)
            ->preview();
    }

    /**
     * Attributes that should not be merged.
     * This method should return an array of attribute names that.
     */
    public function getNotMergeableAttributes(): array
    {
        return [];
    }

    /**
     * Get the label for the mergeable record.
     *
     * This method should return a string that represents
     * the record in a human-readable format, such as a name
     * or title. It is used to identify the record in logs,
     * notifications, and other user interfaces. And is
     * particularly helpful to identify the
     * deleted records after a merge.
     */
    public function getMergeableLabel(): ?string
    {
        return $this->name ?? $this->label ?? $this->title ?? $this->getKey();
    }

    /**
     * Get the possible records for merging based on a search term.
     *
     * This method should return a collection of records that match the search term.
     * The amount parameter can be used to limit the number of results returned.
     * The query is designed to run on the source model and should
     * include the model ID in the search.
     *
     * We support Scout out of the box, but if the model does not
     * support Scout, we fall back to a simple query. You would want to
     * extend this method in your model to add any additional
     * search logic that you need.
     */
    public function getPossibleRecordsForMerging(string $search, int $amount = 10): Collection
    {
        if (method_exists($this, 'toSearchableArray')) {
            return static::search($search)
                ->whereNotIn($this->getKeyName(), [$this->getKey()])
                ->take($amount)
                ->get();
        }

        return static::query()
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->take($amount)
            ->get();
    }
}
