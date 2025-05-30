<?php

namespace Bernskiold\LaravelRecordMerge\Loggers;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\MergeLogger;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Illuminate\Contracts\Auth\Authenticatable;

use function method_exists;
use function trait_exists;

class SpatieActivityLogMergeLogger implements MergeLogger
{
    public function log(Mergeable $source, Mergeable $target, ?MergeData $data = null, ?Authenticatable $performedBy = null): void
    {
        if (!trait_exists('Spatie\Activitylog\Trait\LogsActivity')) {
            return;
        }

        if (!function_exists('activity')) {
            return;
        }

        activity()
            ->performedOn($source)
            ->causedBy($performedBy)
            ->event('merged-into')
            ->withProperties([
                'merged_into_type' => $target->getMorphClass(),
                'merged_into_id' => $target->getKey(),
                'merged_into_label' => $target->getMergeableLabel(),
            ])
            ->log('The record was merged.');

        activity()
            ->performedOn($target)
            ->causedBy($performedBy)
            ->event('merge-received')
            ->withProperties([
                'merged_from_type' => $source->getMorphClass(),
                'merged_from_id' => $source->getKey(),
                'merged_from_label' => $source->getMergeableLabel(),
            ])
            ->log('A record was merged into this one.');
    }
}
