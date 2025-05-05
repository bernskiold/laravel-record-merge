<?php

namespace Bernskiold\LaravelRecordMerge\Contracts;

interface RelationshipHandler
{

    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void;

}