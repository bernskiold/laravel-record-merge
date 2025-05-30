<?php

namespace Bernskiold\LaravelRecordMerge\Data;

class RelationshipCount
{
    public function __construct(
        public readonly string $relationship,
        public readonly int $sourceCount,
        public readonly int $targetCount,
    ) {}
}
