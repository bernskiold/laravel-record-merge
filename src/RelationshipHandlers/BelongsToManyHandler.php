<?php

namespace Bernskiold\LaravelRecordMerge\RelationshipHandlers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use function data_get;

/**
 * Handles merging of BelongsToMany relationships.
 *
 * This handler will detach all existing relations from the source model
 * and attach the related models to the target model, preserving pivot data.
 *
 * It will skip any relations that already exist on the target model.
 */
class BelongsToManyHandler implements RelationshipHandler
{
    public function handle(Mergeable $source, Mergeable $target, string $relationshipName): void
    {
        /** @var BelongsToMany $sourceRelation */
        $sourceRelation = $source->{$relationshipName}();
        
        /** @var BelongsToMany $targetRelation */
        $targetRelation = $target->{$relationshipName}();

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
                if ($column !== $foreignPivotKey && $column !== $relatedPivotKey) {
                    $pivotData[$column] = data_get($relatedModel, "pivot.{$column}");
                }
            }

            // Attach with pivot data
            $targetRelation->attach($relatedId, $pivotData);
        }
    }
}
