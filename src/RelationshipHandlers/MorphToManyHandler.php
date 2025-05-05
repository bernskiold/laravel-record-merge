<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MorphToManyHandler implements RelationshipHandler
{

    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /**
         * @var MorphToMany $relation
         */
        $relation = $source->$relationshipName();

        $pivotTable = $relation->getTable();
        $morphType = $relation->getMorphType();
        $foreignPivotKey = $relation->getForeignPivotKeyName();
        $relatedPivotKey = $relation->getRelatedPivotKeyName();

        // @todo
    }

}