<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BelongsToManyHandler implements RelationshipHandler
{

    /**
     * @todo Add support for pivot columns.
     */
    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /**
         * @var BelongsToMany $relation
         */
        $relation = $source->$relationshipName();

        $pivotTable = $relation->getTable();
        $foreignPivotKey = $relation->getForeignPivotKeyName();
        $relatedPivotKey = $relation->getRelatedPivotKeyName();

        // Get all the related model IDs.
        $relatedIds = $relation->pluck($relatedPivotKey)->toArray();

        // Detach all relations from the old model.
        $relation->detach();

        // Attach the relations to the target model and avoid duplicates.
        $existingRelations = $target->$relationshipName()->pluck($relatedPivotKey)->toArray();
        $relationsToAdd = array_diff($relatedIds, $existingRelations);

        if (!empty($relationsToAdd)) {
            $target->$relationshipName()->attach($relationsToAdd);
        }
    }

}