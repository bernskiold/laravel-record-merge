<?php

namespace Bernskiold\LaravelRecordMerge;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\MergeLogger;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Bernskiold\LaravelRecordMerge\Data\AttributeComparison;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Bernskiold\LaravelRecordMerge\Data\RelationshipCount;
use Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException;
use Bernskiold\LaravelRecordMerge\Exceptions\RelationshipHandlerException;
use Bernskiold\LaravelRecordMerge\RelationshipHandlers\BelongsToManyHandler;
use Bernskiold\LaravelRecordMerge\RelationshipHandlers\HasManyHandler;
use Bernskiold\LaravelRecordMerge\RelationshipHandlers\HasOneHandler;
use Bernskiold\LaravelRecordMerge\RelationshipHandlers\MorphManyHandler;
use Bernskiold\LaravelRecordMerge\RelationshipHandlers\MorphToManyHandler;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;
use function method_exists;
use function trait_exists;

class RecordMerge
{

    public static array $defaultHandlers = [
        HasMany::class => HasManyHandler::class,
        HasOne::class => HasOneHandler::class,
        BelongsToMany::class => BelongsToManyHandler::class,
        BelongsTo::class => false,
        HasOneThrough::class => false,
        HasManyThrough::class => false,
        HasOneOrManyThrough::class => false,
        MorphTo::class => false,
        MorphMany::class => MorphManyHandler::class,
        MorphToMany::class => MorphToManyHandler::class,
    ];

    protected ?Closure $afterMergingCallback = null;

    public function __construct(
        protected ?Mergeable $source = null,
        protected ?Mergeable $target = null,
    )
    {
    }

    public static function new(?Mergeable $source = null, ?Mergeable $target = null): static
    {
        return new static($source, $target);
    }


    /**
     * @throws InvalidRecordMergeException
     */
    public function preview(): MergeData
    {
        $this->validate();

        return new MergeData(
            source: $this->source,
            target: $this->target,
            relationshipCounts: $this->getCountByRelationship(),
            comparison: $this->getAttributeComparison(),
        );
    }

    public function merge(): Mergeable
    {
        $details = $this->preview();

        DB::beginTransaction();

        try {
            $this->updateRelationships();
            $this->log($details);

            // Call the after merging callback if it exists.
            if ($this->afterMergingCallback) {
                ($this->afterMergingCallback)($this->source, $this->target);
            }

            DB::commit();

            return $this->target->fresh();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function validate(): void
    {
        $targetClass = get_class($this->target);

        if ($this->source === null) {
            throw new InvalidArgumentException('The source model is not set.');
        }

        if ($this->target === null) {
            throw new InvalidArgumentException('The target model is not set.');
        }

        if (!$this->source instanceof $targetClass) {
            throw InvalidRecordMergeException::notSameModel($this->source, $this->target);
        }

        if ($this->source->getKey() === $this->target->getKey()) {
            throw InvalidRecordMergeException::sameId($this->source, $this->target);
        }
    }

    /**
     * @return array<string, AttributeComparison>
     */
    protected function getAttributeComparison(): array
    {
        $sourceAttributes = $this->source->getAttributes();
        $targetAttributes = $this->target->getAttributes();

        $comparison = [];

        // Create a list of all the attributes from both models.
        $allKeys = array_unique(array_merge(array_keys($sourceAttributes), array_keys($targetAttributes)));

        foreach ($allKeys as $key) {

            // Skip the key.
            if ($key === $this->source->getKeyName()) {
                continue;
            }

            // Skip attributes that are not mergeable.
            if (in_array($key, $this->source->getNotMergeableAttributes(), true)) {
                continue;
            }

            $comparison[$key] = new AttributeComparison(
                sourceValue: $sourceAttributes[$key] ?? null,
                targetValue: $targetAttributes[$key] ?? null,
            );
        }

        return $comparison;
    }

    /**
     * @return array<string, RelationshipCount>
     */
    protected function getCountByRelationship(): array
    {
        $relationships = static::getRelationshipsForModel($this->source);

        $counts = [];

        foreach ($relationships as $relationship) {
            $counts[$relationship] = new RelationshipCount(
                relationship: $relationship,
                sourceCount: $this->source->{$relationship}()->count(),
                targetCount: $this->target->{$relationship}()->count(),
            );
        }

        return $counts;
    }

    protected static function getRelationshipsForModel(Mergeable $model): array
    {
        $reflection = new \ReflectionClass($model);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $relationships = [];

        foreach ($methods as $method) {

            // Skip methods on the Eloquent base class
            if ($method->class === Model::class) {
                continue;
            }

            // Only consider methods with no required parameters
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();

            // If no return type is defined, we can't determine if it's a relation
            if (!$returnType) {
                continue;
            }

            $returnTypeName = $returnType->getName();

            // Check if return type is a Relation or a subclass of Relation
            if ($returnTypeName === Relation::class || is_subclass_of($returnTypeName, Relation::class)) {
                $relationships[] = $method->getName();
            }
        }

        return $relationships;
    }

    protected function updateRelationships(): void
    {
        $relationships = static::getRelationshipsForModel($this->source);

        foreach ($relationships as $relationship) {
            $relation = $this->source->{$relationship}();
            $handler = $this->getHandlerForRelationship($relation);

            // These are relationships that don't need to be handled,
            // usually because they sit on the model itself.
            if ($handler === false) {
                continue;
            }

            if ($handler === null) {
                throw RelationshipHandlerException::missing($relation);
            }

            $handler->handle($this->source, $this->target, $relationship);
        }
    }

    protected function getHandlerForRelationship(Relation $relation): RelationshipHandler|false|null
    {
        $configuredHandlers = config('record-merge.relationship_handlers', []);
        $handlers = array_merge(static::$defaultHandlers, $configuredHandlers);
        $class = get_class($relation);


        $handler = $handlers[$class] ?? null;

        if ($handler === null) {
            return null;
        }

        if ($handler === false) {
            return false;
        }

        return new $handler();
    }

    protected function log(MergeData $details): void
    {
        $loggers = config('record-merge.loggers', []);

        /**
         * @var class-string<MergeLogger> $logger
         */
        foreach ($loggers as $logger) {
            (new $logger)->log($this->source, $this->target, $details);
        }
    }

    public function from(Mergeable $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function to(Mergeable $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function afterMerging(Closure $callback): static
    {
        $this->afterMergingCallback = $callback;

        return $this;
    }
}