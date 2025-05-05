<?php

use Bernskiold\LaravelRecordMerge\Loggers\SpatieActivityLogMergeLogger;

return [

    /**
     * Custom Relationship Handlers.
     *
     * You can define custom relationship handlers here. These are classes that implement
     * the RelationshipHandler interface and are responsible for handling the
     * merging of specific relationships.
     *
     * The key is the fully qualified class name of the relationship type,
     * and the value is the fully qualified class name of the handler.
     */
    'handlers' => [
        // 'Illuminate\Database\Eloquent\Relations\HasMany' => 'App\RelationshipHandlers\CustomHasManyHandler',
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
        'queue' => 'default',
    ],

];
