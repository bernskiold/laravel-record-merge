<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Handles the merging of "has many" relationships.
 *
 * This class is responsible for updating the foreign key of the related models
 * to point to the target model.
 */
class HasManyHandler implements RelationshipHandler
{
    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /**
         * @var HasMany $relation
         */
        $relation = $source->$relationshipName();

        $foreignKey = $relation->getForeignKeyName();
        $localKey = $relation->getLocalKeyName();

        $relation->update([
            $foreignKey => $target->getAttribute($localKey),
        ]);
    }
}
