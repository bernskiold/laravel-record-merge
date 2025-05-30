<?php

namespace Bernskiold\LaravelRecordMerge\Data;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;

class MergeData
{
    public function __construct(
        public readonly Mergeable $source,
        public readonly Mergeable $target,
        /**
         * @var array<string, RelationshipCount>
         */
        public readonly array $relationshipCounts,
        /**
         * @var array<string, AttributeComparison>
         */
        public readonly array $comparison,
    ) {}
}
