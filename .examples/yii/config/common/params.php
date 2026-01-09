<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\Search\BlogReindexProvider;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

return [
    'application' => require __DIR__ . '/application.php',

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => null,
        'layout' => '@src/Web/Shared/Layout/Main/layout.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],

    'cmsig/seal-yii-module' => [
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
                'adapter' => 'loupe://runtime/indexes',
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
