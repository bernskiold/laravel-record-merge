<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MorphToManyHandler implements RelationshipHandler
{
    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /**
         * @var MorphToMany $relation
         */
        $relation = $source->$relationshipName();

        $morphType = $relation->getMorphType();
        $foreignPivotKey = $relation->getForeignPivotKeyName();
        $relatedPivotKey = $relation->getRelatedPivotKeyName();

        // Get all the related model IDs with their pivot data
        $relatedModels = $relation->withPivot($relation->getPivotColumns())->get();

        // Detach all relations from the old model
        $relation->detach();

        // Get existing relations from the target model
        $existingRelations = $target->$relationshipName()->pluck($relatedPivotKey)->toArray();

        // Process each related model
        foreach ($relatedModels as $relatedModel) {
            $relatedId = $relatedModel->getKey();

            // Skip if this relation already exists on the target
            if (in_array($relatedId, $existingRelations)) {
                continue;
            }

            // Get pivot data for this relation
            $pivotData = [];
            foreach ($relation->getPivotColumns() as $column) {
                if ($column !== $foreignPivotKey && $column !== $relatedPivotKey && $column !== $morphType) {
                    $pivotData[$column] = $relatedModel->pivot->{$column};
                }
            }

            // Attach with pivot data
            $target->$relationshipName()->attach($relatedId, $pivotData);
        }
    }
}
