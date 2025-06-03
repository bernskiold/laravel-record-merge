<?php

namespace Bernskiold\LaravelRecordMerge\Data;

use Bernskiold\LaravelRecordMerge\Enums\MergeStrategy;
use Illuminate\Support\Arr;

class MergeConfig
{
    /**
     * The merge map configuration.
     *
     * @var array<string, MergeStrategy>
     */
    protected array $map = [];

    /**
     * Create a new merge map configuration.
     *
     * @param  array<string, MergeStrategy>  $map
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * Create a new merge map configuration.
     *
     * @param  array<string, MergeStrategy>  $map
     */
    public static function make(array $map = []): self
    {
        return new self($map);
    }

    /**
     * Get the merge map configuration.
     *
     * @return array<string, MergeStrategy>
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * Check if the merge map is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->map);
    }

    /**
     * Get the merge strategy for an attribute.
     */
    public function getStrategyForAttribute(string $attribute): ?MergeStrategy
    {
        $strategy = Arr::get($this->map, $attribute);

        if (! $strategy) {
            return null;
        }

        if ($strategy instanceof MergeStrategy) {
            return $strategy;
        }

        return MergeStrategy::from($strategy);
    }

    /**
     * Check if an attribute should be merged from source to target.
     */
    public function shouldMergeFromSource(string $attribute): bool
    {
        return $this->getStrategyForAttribute($attribute) === MergeStrategy::UseSource;
    }

    /**
     * Check if an attribute should be kept on the target.
     */
    public function shouldKeepOnTarget(string $attribute): bool
    {
        return $this->getStrategyForAttribute($attribute) === MergeStrategy::UseTarget;
    }

    /**
     * Check if an attribute should be skipped during the merge.
     */
    public function shouldSkip(string $attribute): bool
    {
        return $this->getStrategyForAttribute($attribute) === MergeStrategy::Skip;
    }
}
