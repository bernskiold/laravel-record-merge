<?php

namespace Bernskiold\LaravelRecordMerge\Data;

class AttributeComparison
{
    public function __construct(
        public readonly mixed $sourceValue,
        public readonly mixed $targetValue,
    )
    {
    }
}