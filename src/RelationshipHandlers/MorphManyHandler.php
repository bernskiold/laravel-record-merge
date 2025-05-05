<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MorphManyHandler implements RelationshipHandler
{

    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /**
         * @var MorphMany $relation
         */
        $relation = $source->$relationshipName();

        $morphType = $relation->getMorphType();
        $foreignKey = $relation->getForeignKeyName();

        $relation->update([
            $foreignKey => $target->getKey(),
            $morphType => $target->getMorphClass(),
        ]);
    }

}