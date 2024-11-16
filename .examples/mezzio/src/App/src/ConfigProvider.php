<?php

declare(strict_types=1);

namespace App;

use App\Search\BlogReindexProvider;

/**
 * The configuration provider for the App module.
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array.
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'cmsig_seal' => [
                'index_name_prefix' => \getenv('TEST_INDEX_PREFIX') ?: $_ENV['TEST_INDEX_PREFIX'] ?? '',
                'schemas' => [
                    'algolia' => [
                        'dir' => 'config/schemas',
                        'engine' => 'algolia',
                    ],
                    'elasticsearch' => [
                        'dir' => 'config/schemas',
                        'engine' => 'elasticsearch',
                    ],
                    'loupe' => [
                        'dir' => 'config/schemas',
                        'engine' => 'loupe',
                    ],
                    'meilisearch' => [
                        'dir' => 'config/schemas',
                        'engine' => 'meilisearch',
                    ],
                    'memory' => [
                        'dir' => 'config/schemas',
                        'engine' => 'memory',
                    ],
                    'opensearch' => [
                        'dir' => 'config/schemas',
                        'engine' => 'opensearch',
                    ],
                    'redisearch' => [
                        'dir' => 'config/schemas',
                        'engine' => 'redisearch',
                    ],
                    'solr' => [
                        'dir' => 'config/schemas',
                        'engine' => 'solr',
                    ],
                    'typesense' => [
                        'dir' => 'config/schemas',
                        'engine' => 'typesense',
                    ],
                ],
                'engines' => [
                    'algolia' => [
                        'adapter' => (\getenv('ALGOLIA_DSN') ?: $_ENV['ALGOLIA_DSN']),
                    ],
                    'elasticsearch' => [
                        'adapter' => 'elasticsearch://127.0.0.1:9200',
                    ],
                    'loupe' => [
                        'adapter' => 'loupe://data/indexes',
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
                    BlogReindexProvider::class,
                ],
            ],
        ];
    }

    /**
     * Returns the container dependencies.
     *
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                Handler\PingHandler::class => Handler\PingHandler::class,
                BlogReindexProvider::class => BlogReindexProvider::class,
            ],
            'factories' => [
                Handler\SearchHandler::class => Handler\SearchHandlerFactory::class,
                Handler\SearchAlgoliaHandler::class => Handler\SearchAlgoliaHandlerFactory::class,
                Handler\SearchElasticsearchHandler::class => Handler\SearchElasticsearchHandlerFactory::class,
                Handler\SearchLoupeHandler::class => Handler\SearchLoupeHandlerFactory::class,
                Handler\SearchMeilisearchHandler::class => Handler\SearchMeilisearchHandlerFactory::class,
                Handler\SearchMemoryHandler::class => Handler\SearchMemoryHandlerFactory::class,
                Handler\SearchMultiHandler::class => Handler\SearchMultiHandlerFactory::class,
                Handler\SearchOpensearchHandler::class => Handler\SearchOpensearchHandlerFactory::class,
                Handler\SearchReadWriteHandler::class => Handler\SearchReadWriteHandlerFactory::class,
                Handler\SearchRedisearchHandler::class => Handler\SearchRedisearchHandlerFactory::class,
                Handler\SearchSolrHandler::class => Handler\SearchSolrHandlerFactory::class,
                Handler\SearchTypesenseHandler::class => Handler\SearchTypesenseHandlerFactory::class,
            ],
        ];
    }
}
