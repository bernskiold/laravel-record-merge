<?php

use Bernskiold\LaravelRecordMerge\Loggers\SpatieActivityLogMergeLogger;
use Illuminate\Database\Eloquent\Relations;
use Bernskiold\LaravelRecordMerge\RelationshipHandlers;

return [

    /**
     * Relationship Handlers.
     *
     * You can define relationship handlers here. These are classes that implement
     * the RelationshipHandler interface and are responsible for handling the
     * merging of specific relationships.
     *
     * The key is the fully qualified class name of the relationship type,
     * and the value is the fully qualified class name of the handler.
     */
    'handlers' => [
        Relations\HasMany::class => RelationshipHandlers\HasManyHandler::class,
        Relations\HasOne::class => RelationshipHandlers\HasOneHandler::class,
        Relations\BelongsToMany::class => RelationshipHandlers\BelongsToManyHandler::class,
        Relations\MorphMany::class => RelationshipHandlers\MorphManyHandler::class,
        Relations\MorphToMany::class => RelationshipHandlers\MorphToManyHandler::class,
    ],

    /**
     * Log merges.
     *
     * You can define custom methods of logging merges here. These are classes that implement
     * the MergeLogger interface and are responsible for logging.
     *
     * By default, we support the Spatie Activity Log logger (only if you have the package installed).
     *
     * You can have more than one logger by adding more classes to the array.
     * */
    'loggers' => [
        SpatieActivityLogMergeLogger::class,
    ],

    'queue' => [

        /**
         * The connection to use for the queue.
         *
         * Set this to null to use the default connection.
         */
        'connection' => null,

        /**
         * The queue to dispatch the merge jobs to.
         */
        'queue' => 'default',
    ],
];
