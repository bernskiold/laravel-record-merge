<?php

namespace Bernskiold\LaravelRecordMerge;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Contracts\MergeLogger;
use Bernskiold\LaravelRecordMerge\Contracts\RelationshipHandler;
use Bernskiold\LaravelRecordMerge\Data\AttributeComparison;
use Bernskiold\LaravelRecordMerge\Data\MergeConfig;
use Bernskiold\LaravelRecordMerge\Data\MergeData;
use Bernskiold\LaravelRecordMerge\Data\RelationshipCount;
use Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException;
use Bernskiold\LaravelRecordMerge\Exceptions\RelationshipHandlerException;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Tappable;
use Throwable;

use function array_merge;
use function in_array;

/**
 * @phpstan-consistent-constructor
 */
class RecordMerge
{
    use Conditionable,
        Dumpable,
        Tappable;

    /**
     * Callback to execute after the merging is complete.
     */
    public ?Closure $afterMergingCallback = null;

    /**
     * Whether to delete the source model after merging.
     */
    public bool $deleteSourceAfterMerging = true;

    /**
     * The attributes that are allowed to be merged
     * from the source model to the target model.
     */
    public array $mergeableAttributes = [];

    /**
     * The merge configuration.
     */
    public ?MergeConfig $mergeConfig = null;

    public function __construct(
        public ?Mergeable $source = null,
        public ?Mergeable $target = null,
        public ?Authenticatable $performedBy = null,
    ) {}

    /**
     * Create a new record merge instance.
     */
    public static function new(?Mergeable $source = null, ?Mergeable $target = null, ?Authenticatable $performedBy = null): static
    {
        return new static($source, $target, $performedBy);
    }

    /**
     * Preview the merge operation without actually performing it.
     *
     * This is useful to see what will happen during the merge,
     * and show this to the user before they confirm the merge.
     *
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

    /**
     * Perform the merge operation.
     *
     * This will merge the source model into the target model,
     * updating the target model with the source model's attributes
     * and reassign the source model's relationships to the target model.
     *
     * @throws InvalidRecordMergeException
     * @throws RelationshipHandlerException
     * @throws Throwable
     */
    public function merge(): ?Model
    {
        $details = $this->preview();

        DB::beginTransaction();

        try {
            $this->mergeAttributes();
            $this->reassignRelationships();
            $this->log($details);

            // Call the after merging callback if it exists.
            if ($this->afterMergingCallback) {
                ($this->afterMergingCallback)($this->source, $this->target, $this->performedBy);
            }

            $this->source->delete();

            DB::commit();

            return $this->target->fresh();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validates the settings for the merge.
     *
     * @throws InvalidRecordMergeException
     */
    public function validate(): void
    {
        if ($this->source === null) {
            throw InvalidRecordMergeException::noSource();
        }

        if ($this->target === null) {
            throw InvalidRecordMergeException::noTarget();
        }

        $targetClass = get_class($this->target);

        if (! $this->source instanceof $targetClass) {
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

        return collect(array_keys($sourceAttributes))
            ->merge(array_keys($targetAttributes))
            ->filter(fn ($attribute) => $this->canAttributeBeMerged($attribute))
            ->mapWithKeys(function ($attribute) {
                return [
                    $attribute => new AttributeComparison(
                        sourceValue: $this->source->getAttribute($attribute),
                        targetValue: $this->target->getAttribute($attribute),
                    ),
                ];
            })
            ->all();
    }

    /**
     * Returns the attributes that will be merged
     *
     * This will return an array of attributes that will be merged
     * from the source model to the target model.
     */
    protected function getAttributesToMerge(): array
    {
        return collect($this->source->getAttributes())
            ->filter(fn ($value, $attribute) => $this->canAttributeBeMerged($attribute))
            ->unique()
            ->all();
    }

    /**
     * Merges the attributes from the source model to the target model.
     *
     * This will only merge attributes that are not already set on the target model,
     * and will skip any attributes that are null, so that the merge only
     * adds data and does not remove any existing data on the target model.
     *
     * If a merge map is provided, it will be used to determine how to handle each attribute.
     */
    protected function mergeAttributes(): void
    {
        $attributes = $this->getAttributesToMerge();

        // If there are no attributes to merge, we can skip this step.
        if (empty($attributes)) {
            return;
        }

        foreach ($attributes as $key => $value) {
            $targetHasValue = $this->target->getAttribute($key) !== null;
            $hasNoMergeConfig = $this->mergeConfig === null;
            $shouldNotMergeFromSource = $this->mergeConfig !== null && ! $this->mergeConfig->shouldMergeFromSource($key);

            // If the attribute is already set on the target model and we don't have a merge map
            // or the merge map doesn't specify to merge from source, we skip it.
            if ($targetHasValue && ($hasNoMergeConfig || $shouldNotMergeFromSource)) {
                continue;
            }

            // If the value is null, we skip it.
            if ($value === null) {
                continue;
            }

            // Set the attribute on the target model.
            $this->target->{$key} = $value;
        }

        // Save the target model with the merged attributes.
        $this->target->save();
    }

    /**
     * Returns the amount of relationship models that
     * will be reassigned during the merge.
     *
     * @return array<string, RelationshipCount>
     */
    protected function getCountByRelationship(): array
    {
        return collect(static::getRelationshipsForModel($this->source))
            ->mapWithKeys(function ($relationship) {
                return [
                    $relationship => new RelationshipCount(
                        relationship: $relationship,
                        sourceCount: $this->source->{$relationship}()->count(),
                        targetCount: $this->target->{$relationship}()->count(),
                    ),
                ];
            })
            ->all();
    }

    /**
     * Get all the relationships defined on the model.
     *
     * This will return an array of relationship method names
     * that are defined on the model. Relationships are
     * auto-discovered if they extend "Relation".
     */
    protected static function getRelationshipsForModel(Mergeable $model): array
    {
        $reflection = new \ReflectionClass($model);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $protectedRelationships = $model->getProtectedRelationships();

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
            if (! $returnType instanceof \ReflectionNamedType) {
                continue;
            }

            $returnTypeName = $returnType->getName();

            if ($returnTypeName !== Relation::class && ! is_subclass_of($returnTypeName, Relation::class)) {
                continue;
            }

            // Skip protected relationships that are not meant to be merged
            if (in_array($method->getName(), $protectedRelationships, false)) {
                continue;
            }

            $relationships[] = $method->getName();
        }

        return $relationships;
    }

    /**
     * Reassign the relationships from the source model to the target model.
     *
     * This will loop through all the relationships defined on the source model,
     * and use the appropriate handler to reassign them to the target model.
     *
     * @throws RelationshipHandlerException
     */
    protected function reassignRelationships(): void
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

    /**
     * Get the handler for a given relationship.
     *
     * This will return an instance of the handler if it exists,
     * or null if no handler is defined.
     */
    protected function getHandlerForRelationship(Relation $relation): RelationshipHandler|false|null
    {
        /**
         * These are handlers that by default don't need to be handled.
         * They can be overridden in the config file if really necessary,
         * but they need to be skipped by defauly.
         */
        $defaultHandlers = [
            BelongsTo::class => false, // This is handled by the attribute mapping.
            HasOneThrough::class => false, // This is handled by the direct relationship.
            HasManyThrough::class => false, // This is handled by the direct relationship.
            HasOneOrManyThrough::class => false, // This is handled by the direct relationship.
            MorphTo::class => false, // This is handled by the attribute mapping.
        ];

        $handlers = config('record-merge.handlers', []);
        $handlers = array_merge($defaultHandlers, $handlers);

        $class = get_class($relation);

        $handler = Arr::get($handlers, $class);

        // If the handler is not defined, return null.
        if ($handler === null) {
            return null;
        }

        // If the handler is false, we don't handle this relationship.
        if ($handler === false) {
            return false;
        }

        return new $handler;
    }

    /**
     * Checks if an attribute can be merged.
     */
    public function canAttributeBeMerged(string $attribute): bool
    {
        // If we have a merge map and the attribute should be skipped, we don't merge it.
        if ($this->mergeConfig && $this->mergeConfig->shouldSkip($attribute)) {
            return false;
        }

        // If we have a merge map and the attribute should be kept on the target, we don't merge it.
        if ($this->mergeConfig && $this->mergeConfig->shouldKeepOnTarget($attribute)) {
            return false;
        }

        // If we have a list of allowed attributes, we only merge those.
        if (! empty($this->mergeableAttributes) && ! in_array($attribute, $this->mergeableAttributes, true)) {
            return false;
        }

        // We do not allow the primary key to be merged.
        if ($attribute === $this->source->getKeyName()) {
            return false;
        }

        // We do not allow core timestamps to be merged.
        if (in_array($attribute, [$this->source->getCreatedAtColumn(), $this->source->getUpdatedAtColumn()], true)) {
            return false;
        }

        // For soft deletes, we do not allow the deleted_at attribute to be merged.
        if (method_exists($this->source, 'getDeletedAtColumn') && $attribute === $this->source->getDeletedAtColumn()) {
            return false;
        }

        // We do not allow attributes that are not mergeable.
        if (in_array($attribute, $this->target->getNotMergeableAttributes(), true)) {
            return false;
        }

        return true;
    }

    /**
     * Log the details of the merge operating.
     *
     * This will use the configured loggers to log the merge details.
     */
    protected function log(MergeData $details): void
    {
        $loggers = config('record-merge.loggers', []);

        /**
         * @var class-string<MergeLogger> $logger
         */
        foreach ($loggers as $logger) {
            (new $logger)->log($this->source, $this->target, $details, $this->performedBy);
        }
    }

    /**
     * Set the model that you want to merge from (ie. not keep).
     */
    public function from(Mergeable $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Set the model that you want to merge into (ie. keep).
     */
    public function to(Mergeable $target): static
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Execute code after the merging process is complete.
     * This is useful for additional cleanup or actions after the merge.
     *
     * The callback will receive the source and target models as parameters.
     *
     * @param  Closure(Mergeable $source, Mergeable $target, ?Authenticatable $performedBy): void  $callback
     */
    public function afterMerging(Closure $callback): static
    {
        $this->afterMergingCallback = $callback;

        return $this;
    }

    /**
     * Set the user that performed the merge.
     *
     * This is useful for logging and auditing purposes.
     */
    public function performedBy(?Authenticatable $user): static
    {
        $this->performedBy = $user;

        return $this;
    }

    public function deleteAfterMerging(bool $delete = true): static
    {
        $this->deleteSourceAfterMerging = $delete;

        return $this;
    }

    public function dontDeleteAfterMerging(): static
    {
        return $this->deleteAfterMerging(false);
    }

    public function allowedAttributes(array $attributes): static
    {
        $this->mergeableAttributes = $attributes;

        return $this;
    }

    public function allowAttributes(string|array $attributes): static
    {
        if (is_string($attributes)) {
            $attributes = explode(',', $attributes);
        }

        $attributes = array_unique(array_merge(
            $this->mergeableAttributes,
            array_map('trim', $attributes)
        ));

        return $this->allowedAttributes($attributes);
    }

    /**
     * Set the merge map configuration.
     */
    public function withMergeConfig(?MergeConfig $mergeConfig = null): static
    {
        $this->mergeConfig = $mergeConfig;

        return $this;
    }
}
