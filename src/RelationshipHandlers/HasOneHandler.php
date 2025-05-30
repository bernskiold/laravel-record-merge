<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Handles the merging of HasOne relationships.
 *
 * This class is responsible for updating the foreign key of the related model
 * to point to the target model after a merge operation.
 */
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
