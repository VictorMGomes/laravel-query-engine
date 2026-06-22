<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Metadata Connection
    |--------------------------------------------------------------------------
    |
    | This is the database connection that will be used to inspect model
    | attributes and generate filters/sorts/etc.
    |
    */
    'metadata_connection' => env('QUERY_PARAMS_METADATA_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    */
    'caching' => [
        'enabled' => env('QUERY_PARAMS_CACHE_ENABLED', true),
        'ttl' => env('QUERY_PARAMS_CACHE_TTL', 3600),
    ],

    'force_cache' => env('QUERY_PARAMS_FORCE_CACHE', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will log the generated rules for each
    | form request that uses MapQueryEngine.
    |
    */
    'debug' => env('QUERY_PARAMS_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Pluggable Drivers
    |--------------------------------------------------------------------------
    |
    | Define custom resolvers for specific field behaviors
    |
    */
    'drivers' => [
        // 'default' => App\Support\QueryDrivers\CustomDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Security & Feature Controls
    |--------------------------------------------------------------------------
    |
    | Define which query features are enabled globally. Disabling a feature
    | here will reject any incoming parameters associated with that feature.
    |
    */
    'features' => [
        'filters' => env('QUERY_PARAMS_ENABLE_FILTERS', true),
        'sorts' => env('QUERY_PARAMS_ENABLE_SORTS', true),
        'includes' => env('QUERY_PARAMS_ENABLE_INCLUDES', true),
        'fields' => env('QUERY_PARAMS_ENABLE_FIELDS', true),
        'page' => env('QUERY_PARAMS_ENABLE_PAGINATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Define strict limits for pagination to prevent users from requesting
    | massive datasets and crashing the server memory.
    |
    */
    'pagination' => [
        'max_limit' => env('QUERY_PARAMS_MAX_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Filter Operators
    |--------------------------------------------------------------------------
    |
    | Whitelist of operators that clients are allowed to use for filtering.
    | Any operator not present here will be completely disabled.
    |
    */
    'allowed_operators' => [
        'eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'in', 'nin', 'null', 'notnull',
        'between', 'nbetween', 'like', 'notlike', 'ilike', 'notilike', 'contains',
        'containedby', 'overlap', 'fts', 'or', 'and', 'not', 'exists', 'notexists',
        'year', 'month', 'day', 'date', 'time',
    ],
];
