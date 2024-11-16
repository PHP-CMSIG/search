<?php

declare(strict_types=1);

return [
    'index_name_prefix' => env('TEST_INDEX_PREFIX', ''),
    'schemas' => [
        'algolia' => [
            'dir' => 'app/schemas',
            'engine' => 'algolia',
        ],
        'elasticsearch' => [
            'dir' => 'app/schemas',
            'engine' => 'elasticsearch',
        ],
        'loupe' => [
            'dir' => 'app/schemas',
            'engine' => 'loupe',
        ],
        'meilisearch' => [
            'dir' => 'app/schemas',
            'engine' => 'meilisearch',
        ],
        'memory' => [
            'dir' => 'app/schemas',
            'engine' => 'memory',
        ],
        'opensearch' => [
            'dir' => 'app/schemas',
            'engine' => 'opensearch',
        ],
        'redisearch' => [
            'dir' => 'app/schemas',
            'engine' => 'redisearch',
        ],
        'solr' => [
            'dir' => 'app/schemas',
            'engine' => 'solr',
        ],
        'typesense' => [
            'dir' => 'app/schemas',
            'engine' => 'typesense',
        ],
    ],
    'engines' => [
        'algolia' => [
            'adapter' => env('ALGOLIA_DSN'),
        ],
        'elasticsearch' => [
            'adapter' => 'elasticsearch://127.0.0.1:9200',
        ],
        'loupe' => [
            'adapter' => 'loupe://runtime/var/indexes',
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
    'reindex_providers' => [
        \App\Search\BlogReindexProvider::class,
    ],
];
