<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HasOneHandler implements RelationshipHandler
{

    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /**
         * @var HasOne $relation
         */
        $relation = $source->$relationshipName();

        $foreignKey = $relation->getForeignKeyName();
        $localKey = $relation->getLocalKeyName();

        $related = $relation->getResults();

        if ($related) {
            $related->update([
                $foreignKey => $target->getAttribute($localKey),
            ]);
        }
    }

}