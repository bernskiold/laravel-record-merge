<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

use function data_get;

/**
 * Handles merging of MorphToMany relationships.
 *
 * This class is responsible for merging MorphToMany relationships between two models.
 * It detaches existing relations from the source model and attaches them to the target model,
 * ensuring that pivot data is preserved.
 */
class MorphToManyHandler implements RelationshipHandler
{
    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /** @var MorphToMany $sourceRelation */
        $sourceRelation = $source->$relationshipName();
        
        /** @var MorphToMany $targetRelation */
        $targetRelation = $target->$relationshipName();

        $morphType = $sourceRelation->getMorphType();
        $foreignPivotKey = $sourceRelation->getForeignPivotKeyName();
        $relatedPivotKey = $sourceRelation->getRelatedPivotKeyName();

        // Get all the related model IDs with their pivot data
        $relatedModels = $sourceRelation->withPivot($sourceRelation->getPivotColumns())->get();

        // Detach all relations from the old model
        $sourceRelation->detach();

        // Get existing relations from the target model
        $existingRelations = $targetRelation->pluck($relatedPivotKey)->toArray();

        // Process each related model
        foreach ($relatedModels as $relatedModel) {
            $relatedId = $relatedModel->getKey();

            // Skip if this relation already exists on the target
            if (in_array($relatedId, $existingRelations)) {
                continue;
            }

            // Get pivot data for this relation
            $pivotData = [];

            foreach ($sourceRelation->getPivotColumns() as $column) {
                if ($column !== $foreignPivotKey && $column !== $relatedPivotKey && $column !== $morphType) {
                    $pivotData[$column] = data_get($relatedModel, "pivot.{$column}");
                }
            }

            // Attach with pivot data
            $targetRelation->attach($relatedId, $pivotData);
        }
    }
}
