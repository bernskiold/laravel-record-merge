<?php

namespace Bernskiold\LaravelRecordMerge\Exceptions;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Exception;

class InvalidRecordMergeException extends Exception
{
    /**
     * The source model that was attempted to be merged.
     */
    protected ?Mergeable $source = null;

    /**
     * The target model that was attempted to merge the source into.
     */
    protected ?Mergeable $target = null;

    public static function noSource(): self
    {
        return new self('No source model was provided for merging from.');
    }

    public static function noTarget(): self
    {
        return new self('No target model was provided for merging into.');
    }

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
        $instance = new self('The source model and the target model are the same.');
        $instance->source = $source;
        $instance->target = $target;

        return $instance;
    }

    /**
     * Get the source model that was attempted to be merged.
     */
    public function getSource(): ?Mergeable
    {
        return $this->source;
    }

    /**
     * Get the target model that was attempted to merge the source into.
     */
    public function getTarget(): ?Mergeable
    {
        return $this->target;
    }
}
