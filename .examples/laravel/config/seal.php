<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Schema prefix
    |--------------------------------------------------------------------------
    |
    | Define the prefix used for the index names to avoid conflicts.
    */
    'index_name_prefix' => env('TEST_INDEX_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Schema configs
    |--------------------------------------------------------------------------
    |
    | Define different directories for the schema loader.
    */

    'schemas' => [
        'algolia' => [
            'dir' => resource_path('schemas'),
            'engine' => 'algolia',
        ],
        'elasticsearch' => [
            'dir' => resource_path('schemas'),
            'engine' => 'elasticsearch',
        ],
        'loupe' => [
            'dir' => resource_path('schemas'),
            'engine' => 'loupe',
        ],
        'meilisearch' => [
            'dir' => resource_path('schemas'),
            'engine' => 'meilisearch',
        ],
        'memory' => [
            'dir' => resource_path('schemas'),
            'engine' => 'memory',
        ],
        'opensearch' => [
            'dir' => resource_path('schemas'),
            'engine' => 'opensearch',
        ],
        'redisearch' => [
            'dir' => resource_path('schemas'),
            'engine' => 'redisearch',
        ],
        'solr' => [
            'dir' => resource_path('schemas'),
            'engine' => 'solr',
        ],
        'typesense' => [
            'dir' => resource_path('schemas'),
            'engine' => 'typesense',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | engines
    |--------------------------------------------------------------------------
    |
    | Directory where the latte templates can be found.
    */

    'engines' => [
        'algolia' => [
            'adapter' => env('ALGOLIA_DSN'),
        ],
        'elasticsearch' => [
            'adapter' => 'elasticsearch://127.0.0.1:9200',
        ],
        'loupe' => [
            'adapter' => 'loupe://storage/indexes',
        ],
        'meilisearch' => [
            'adapter' => 'meilisearch://127.0.0.1:7700',
        ],
        'memory' => [
            'adapter' => 'memory://',
        ],
        'opensearch' => [
            'adapter' => 'opensearch://127.0.0.1:9201',
        ],
        'redisearch' => [
            'adapter' => 'redis://supersecure@127.0.0.1:6379',
        ],
        'solr' => [
            'adapter' => 'solr://127.0.0.1:8983',
        ],
        'typesense' => [
            'adapter' => 'typesense://S3CR3T@127.0.0.1:8108',
        ],

        // ...
        'multi' => [
            'adapter' => 'multi://elasticsearch?adapters[]=opensearch',
        ],
        'read-write' => [
            'adapter' => 'read-write://elasticsearch?write=multi',
        ],
    ],
];
