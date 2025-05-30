<?php

namespace Bernskiold\LaravelRecordMerge\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;

class RelationshipHandlerException extends Exception
{
    public static function missing(Relation $relation): self
    {
        $relationClass = get_class($relation);

        return new self("A relationship handler for {$relationClass} could not be found.");
    }
}
