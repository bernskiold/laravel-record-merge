<?php

namespace Bernskiold\LaravelRecordMerge\Exceptions;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Exception;

class InvalidRecordMergeException extends Exception
{

    protected Mergeable $source;

    protected Mergeable $target;

    public static function notSameModel(Mergeable $source, Mergeable $target): self
    {
        $sourceClass = get_class($source);
        $targetClass = get_class($target);

        $instance = new self("The source model [{$sourceClass}] and target model [{$targetClass}] are not the same.");
        $instance->source = $source;
        $instance->target = $target;

        return $instance;
    }

    public static function sameId(Mergeable $source, Mergeable $target): self
    {
        $instance = new self("The source model and the target model are the same.");
        $instance->source = $source;
        $instance->target = $target;

        return $instance;
    }

    public function getSource(): Mergeable
    {
        return $this->source;
    }

    public function getTarget(): Mergeable
    {
        return $this->target;
    }

}