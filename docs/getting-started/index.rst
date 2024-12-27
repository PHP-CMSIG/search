Getting Started
===============

Lets get started with the **Search Engine Abstraction Layer** library for PHP.

In this part we will show how you can start using SEAL in your project and its basic functions.

Installation
------------

To install the package you need to use `Composer <https://getcomposer.org>`_ as the packages are registered there.
Depending on your project you can decide to use already existing ``Framework``
integration of the package or the ``Standalone`` version.

.. tabs::

    .. group-tab:: Standalone use

        If you want to use standalone version use the following package:

        .. code-block:: bash

            composer require cmsig/seal

    .. group-tab:: Laravel

        If you are using `Laravel <https://laravel.com/>`_ use the following packages:

        .. code-block:: bash

             composer require cmsig/seal-laravel-package

    .. group-tab:: Symfony

        If you are using `Symfony <https://symfony.com/>`_ use the following packages:

        .. code-block:: bash

             composer require cmsig/seal-symfony-bundle

    .. group-tab:: Spiral

        If you are using `Spiral <https://spiral.dev/>`_ use the following packages:

        .. code-block:: bash

             composer require cmsig/seal-spiral-bridge

    .. group-tab:: Mezzio

        If you are using `Mezzio <https://docs.mezzio.dev/>`_ use the following packages:

        .. code-block:: bash

             composer require cmsig/seal-mezzio-module

    .. group-tab:: Yii

        If you are using `Yii <https://www.yiiframework.com/>`_ use the following packages:

        .. code-block:: bash

             composer require cmsig/seal-yii-module

| The project provides adapters to different search engines, atleast one is required.
| Choose the one which fits your needs best:

.. tabs::

    .. group-tab:: Meilisearch

        Install the `Meilisearch <https://www.meilisearch.com/>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-meilisearch-adapter

    .. group-tab:: Algolia

        Install the `Algolia <https://www.algolia.com/>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-algolia-adapter

    .. group-tab:: Elasticsearch

        Install the `Elasticsearch <https://www.elastic.co/what-is/elasticsearch>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-elasticsearch-adapter

    .. group-tab:: Opensearch

        Install the `Opensearch <https://opensearch.org>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-opensearch-adapter

    .. group-tab:: Redisearch

        Install the `Redisearch <https://redis.io/docs/stack/search/>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-redisearch-adapter

    .. group-tab:: Loupe

        Install the `Loupe <https://github.com/loupe-php/loupe>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-loupe-adapter

    .. group-tab:: Solr

        Install the `Solr <https://solr.apache.org/>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-solr-adapter

    .. group-tab:: Typesense

        Install the `Typesense <https://typesense.org/>`__ adapter:

        .. code-block:: bash

            composer require cmsig/seal-typesense-adapter

Configure Schema
----------------

The ``Schema`` defines the different ``Indexes`` and their ``Fields``.
The definition of the fields depends on which data you want to store (text, int, float, ...) in the search engine
and what you want todo with it later (searchable, filterable, sortable, ...).

In this section we will create a first schema for our ``Index``:

.. tabs::

    .. group-tab:: Standalone use

        When using the ``Standalone`` version you need to create a new ``Index``
        instance as part of the ``Schema``:

        .. code-block:: php

            <?php

            use CmsIg\Seal\Schema\Field;
            use CmsIg\Seal\Schema\Index;
            use CmsIg\Seal\Schema\Schema;

            $schema = new Schema([
                'blog' => new Index('blog', [
                    'id' => new Field\IdentifierField('id'),
                    'title' => new Field\TextField('title'),
                    'description' => new Field\TextField('description'),
                    'tags' => new Field\TextField('tags', multiple: true, filterable: true),
                ]),
            ]);

    .. group-tab:: Laravel

        If you are using Laravel create a new ``Index`` in the ``resources/schemas`` directory:

        .. code-block:: php

            <?php // resources/schemas/blog.php

            use CmsIg\Seal\Schema\Field;
            use CmsIg\Seal\Schema\Index;

            return new Index('blog', [
                'id' => new Field\IdentifierField('id'),
                'title' => new Field\TextField('title'),
                'description' => new Field\TextField('description'),
                'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            ]);

    .. group-tab:: Symfony

        If you are using Symfony create a new ``Index`` in the ``resources/schemas`` directory:

        .. code-block:: php

            <?php // config/schemas/blog.php

            use CmsIg\Seal\Schema\Field;
            use CmsIg\Seal\Schema\Index;

            return new Index('blog', [
                'id' => new Field\IdentifierField('id'),
                'title' => new Field\TextField('title'),
                'description' => new Field\TextField('description'),
                'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            ]);

    .. group-tab:: Spiral

        If you are using Spiral create a new ``Index`` in the ``resources/schemas`` directory:

        .. code-block:: php

            <?php // app/schemas/blog.php

            use CmsIg\Seal\Schema\Field;
            use CmsIg\Seal\Schema\Index;

            return new Index('blog', [
                'id' => new Field\IdentifierField('id'),
                'title' => new Field\TextField('title'),
                'description' => new Field\TextField('description'),
                'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            ]);

    .. group-tab:: Mezzio

        If you are using Mezzio create a new ``Index`` in the ``config/schemas`` directory:

        .. code-block:: php

            <?php // config/schemas/blog.php

            use CmsIg\Seal\Schema\Field;
            use CmsIg\Seal\Schema\Index;

            return new Index('blog', [
                'id' => new Field\IdentifierField('id'),
                'title' => new Field\TextField('title'),
                'description' => new Field\TextField('description'),
                'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            ]);

    .. group-tab:: Yii

        If you are using Yii create a new ``Index`` in the ``config/schemas`` directory:

        .. code-block:: php

            <?php // config/schemas/blog.php

            use CmsIg\Seal\Schema\Field;
            use CmsIg\Seal\Schema\Index;

            return new Index('blog', [
                'id' => new Field\IdentifierField('id'),
                'title' => new Field\TextField('title'),
                'description' => new Field\TextField('description'),
                'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            ]);

For a full list of available fields see the :doc:`../schema/index` documentation. The
only required field is the ``IdentifierField`` which can appear only once per index.

Configure Engine
----------------

In the next step we will create the engine which will be use our created ``Schema``.
The ``Engine`` is the main class which will be used to communicate with the search engine.
So for all kind of operations like add, remove, search, filter, drop, create, ... we need to use the ``Engine``.

It requires an instance of the ``Adapter`` which we did install before to connect to the correct Search engine.

.. tabs::

    .. group-tab:: Standalone use

        When using the ``Standalone`` version we need to create a new instance of ``Engine``
        class to create it. The ``Engine`` requires beside the already created ``Schema`` also
        an instance of ``Adapter`` which will be used to communicate with the search engine.

        .. tabs::

            .. group-tab:: Meilisearch

                Use the following code to create a new ``Engine`` using the ``Meilisearch`` adapter:

                .. code-block:: php

                    <?php

                    use Meilisearch\Client;
                    use CmsIg\Seal\Adapter\Meilisearch\MeilisearchAdapter;
                    use CmsIg\Seal\Engine;

                    $client = new Client('http://127.0.0.1:7700');

                    $engine = new Engine(
                        new MeilisearchAdapter($client),
                        $schema,
                    );

            .. group-tab:: Algolia

                Use the following code to create a new ``Engine`` using the ``Algolia`` adapter:

                .. code-block:: php

                    <?php

                    use Algolia\AlgoliaSearch\SearchClient;
                    use CmsIg\Seal\Adapter\Algolia\AlgoliaAdapter;
                    use CmsIg\Seal\Engine;

                    $client = Algolia\AlgoliaSearch\SearchClient::create(
                        'YourApplicationID',
                        'YourAdminAPIKey',
                    );

                    $engine = new Engine(
                        new AlgoliaAdapter($client),
                        $schema,
                    );

            .. group-tab:: Elasticsearch

                Use the following code to create a new ``Engine`` using the ``Elasticsearch`` adapter:

                .. code-block:: php

                    <?php

                    use Elastic\Elasticsearch\ClientBuilder;
                    use CmsIg\Seal\Adapter\Elasticsearch\ElasticsearchAdapter;
                    use CmsIg\Seal\Engine;

                    $client = ClientBuilder::create()->setHosts([
                        '127.0.0.1:9200'
                    ])->build()

                    $engine = new Engine(
                        new ElasticsearchAdapter($client),
                        $schema,
                    );

            .. group-tab:: Opensearch

                Use the following code to create a new ``Engine`` using the ``Opensearch`` adapter:

                .. code-block:: php

                    <?php

                    use OpenSearch\ClientBuilder;
                    use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapter;
                    use CmsIg\Seal\Engine;

                    $client = ClientBuilder::create()->setHosts([
                        '127.0.0.1:9200'
                    ])->build()

                    $engine = new Engine(
                        new OpensearchAdapter($client),
                        $schema,
                    );

            .. group-tab:: Redisearch

                Use the following code to create a new ``Engine`` using the ``Redisearch`` adapter:

                .. code-block:: php

                    <?php

                    use Redis;
                    use CmsIg\Seal\Adapter\RediSearch\RediSearchAdapter;
                    use CmsIg\Seal\Engine;

                    $redis = new Redis([
                        'host' => '127.0.0.1',
                        'port' => 6379,
                        'auth' => ['phpredis', 'phpredis'],
                    ]);

                    $engine = new Engine(
                        new RediSearchAdapter($redis),
                        $schema,
                    );

            .. group-tab:: Loupe

                Use the following code to create a new ``Engine`` using the ``Loupe`` adapter:

                .. code-block:: php

                    <?php

                    use Loupe\Loupe\LoupeFactory;
                    use CmsIg\Seal\Adapter\Loupe\LoupeAdapter;
                    use CmsIg\Seal\Adapter\Loupe\LoupeHelper;
                    use CmsIg\Seal\Engine;

                    $loupeHelper = new LoupeHelper(
                        new LoupeFactory(),
                        $directory,
                    );

                    $engine = new Engine(
                        new LoupeAdapter($loupeHelper),
                        $schema,
                    );

            .. group-tab:: Solr

                Use the following code to create a new ``Engine`` using the ``Solr`` adapter:

                .. code-block:: php

                    <?php

                    use Solr\Client;
                    use Solarium\Core\Client\Adapter\Curl;
                    use CmsIg\Seal\Adapter\Solr\SolrAdapter;
                    use CmsIg\Seal\Engine;
                    use Symfony\Component\EventDispatcher\EventDispatcher;

                    $client = new Client(new Curl(), new EventDispatcher(), [
                        'endpoint' => [
                            'localhost' => [
                                'host' => '127.0.0.1',
                                'port' => '8983',
                                // authenticated required for configset api https://solr.apache.org/guide/8_9/configsets-api.html
                                // alternative set solr.disableConfigSetsCreateAuthChecks=true in your server setup
                                'username' => 'solr',
                                'password' => 'SolrRocks',
                            ],
                        ]
                    ]);

                    $engine = new Engine(
                        new SolrAdapter($client),
                        $schema,
                    );

            .. group-tab:: Typesense

                Use the following code to create a new ``Engine`` using the ``Typesense`` adapter:

                .. code-block:: php

                    <?php

                    use Http\Client\Curl\Client as CurlClient;
                    use Http\Discovery\Psr17FactoryDiscovery;
                    use CmsIg\Seal\Adapter\Typesense\TypesenseAdapter;
                    use CmsIg\Seal\Engine;
                    use Typesense\Client;

                    $client = new Client(
                         [
                            'api_key' => 'S3CR3T',
                            'nodes' => [
                                [
                                    'host' => '127.0.0.1',
                                    'port' => '8108',
                                    'protocol' => 'http',
                                ],
                            ],
                            'client' => new CurlClient(Psr17FactoryDiscovery::findResponseFactory(), Psr17FactoryDiscovery::findStreamFactory()),
                         ]
                    );

                    $engine = new Engine(
                        new TypesenseAdapter($client),
                        $schema,
                    );

    .. group-tab:: Laravel

        When we are using the Laravel integration package we just need to configure our ``Engine``
        in the ``config/cmsig_seal.php`` file. The ``Adapter`` is configured via a ``DSN`` like string.

        .. tabs::

            .. group-tab:: Meilisearch

                Use the following configuration to use ``Meilisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'meilisearch://127.0.0.1:7700',
                            ],
                        ],
                    ];


            .. group-tab:: Algolia

                Use the following configuration to use ``Algolia`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'algolia://' . env('ALGOLIA_APPLICATION_ID') . ':' . env('ALGOLIA_ADMIN_API_KEY'),
                            ],
                        ],
                    ];

            .. group-tab:: Elasticsearch

                Use the following configuration to use ``Elasticsearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'elasticsearch://127.0.0.1:9200',
                            ],
                        ],
                    ];

            .. group-tab:: Opensearch

                Use the following configuration to use ``Opensearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'opensearch://127.0.0.1:9200',
                            ],
                        ],
                    ];

            .. group-tab:: Redisearch

                Use the following configuration to use ``Redisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'redis://127.0.0.1:6379',
                            ],
                        ],
                    ];

            .. group-tab:: Loupe

                Use the following configuration to use ``Loupe`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'loupe://var/indexes',
                            ],
                        ],
                    ];

            .. group-tab:: Solr

                Use the following configuration to use ``Solr`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'solr://127.0.0.1:8983',
                            ],
                        ],
                    ];

            .. group-tab:: Typesense

                Use the following configuration to use ``Typesense`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'typesense://S3CR3T@127.0.0.1:8108',
                            ],
                        ],
                    ];

        .. note::

            The ``Laravel`` integration provides also `Facades <https://laravel.com/docs/10.x/facades>`__ for the later used default ``Engine``
            and ``EngineRegistry``. They are provided under the ``CmsIg\Seal\Integration\Laravel\Facade\``
            namespace. See also the `Laravel Integration README <https://github.com/php-cmsig/search/tree/0.1/integrations/laravel>`__.

    .. group-tab:: Symfony

        When we are using the Symfony Bundle we just need to configure our ``Engine``
        in the ``config/packages/seal.yaml`` file. The ``Adapter`` is configured
        via a ``DSN`` like string.

        .. tabs::

            .. group-tab:: Meilisearch

                Use the following configuration to use ``Meilisearch`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'meilisearch://127.0.0.1:7700'


            .. group-tab:: Algolia

                Use the following configuration to use ``Algolia`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'algolia://%env(ALGOLIA_APPLICATION_ID)%:%env(ALGOLIA_ADMIN_API_KEY)%'

            .. group-tab:: Elasticsearch

                Use the following configuration to use ``Elasticsearch`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'elasticsearch://127.0.0.1:9200'

            .. group-tab:: Opensearch

                Use the following configuration to use ``Opensearch`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'opensearch://127.0.0.1:9200'

            .. group-tab:: Redisearch

                Use the following configuration to use ``Redisearch`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'redis://127.0.0.1:6379'

            .. group-tab:: Loupe

                Use the following configuration to use ``Loupe`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'loupe://var/indexes'

            .. group-tab:: Solr

                Use the following configuration to use ``Solr`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'solr://127.0.0.1:8983'

            .. group-tab:: Typesense

                Use the following configuration to use ``Typesense`` as your default ``Engine`` adapter:

                .. code-block:: yaml

                    # config/packages/seal.yaml

                    cmsig_seal:
                        schemas:
                            default:
                                dir: '%kernel.project_dir%/config/schemas'
                        engines:
                            default:
                                adapter: 'typesense://S3CR3T@127.0.0.1:8108'

    .. group-tab:: Spiral

        When we are using the Spiral integration package we just need to configure our ``Engine``
        in the ``app/config/cmsig_seal.php`` file. The ``Adapter`` is configured via a ``DSN`` like string.

        .. tabs::

            .. group-tab:: Meilisearch

                Use the following configuration to use ``Meilisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'meilisearch://127.0.0.1:7700',
                            ],
                        ],
                    ];


            .. group-tab:: Algolia

                Use the following configuration to use ``Algolia`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'algolia://' . env('ALGOLIA_APPLICATION_ID') . ':' . env('ALGOLIA_ADMIN_API_KEY'),
                            ],
                        ],
                    ];

            .. group-tab:: Elasticsearch

                Use the following configuration to use ``Elasticsearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'elasticsearch://127.0.0.1:9200',
                            ],
                        ],
                    ];

            .. group-tab:: Opensearch

                Use the following configuration to use ``Opensearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'opensearch://127.0.0.1:9200',
                            ],
                        ],
                    ];

            .. group-tab:: Redisearch

                Use the following configuration to use ``Redisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'redis://127.0.0.1:6379',
                            ],
                        ],
                    ];

            .. group-tab:: Loupe

                Use the following configuration to use ``Loupe`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'loupe://var/indexes',
                            ],
                        ],
                    ];

            .. group-tab:: Solr

                Use the following configuration to use ``Solr`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'solr://127.0.0.1:8983',
                            ],
                        ],
                    ];

            .. group-tab:: Typesense

                Use the following configuration to use ``Typesense`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // app/config/cmsig_seal.php

                    return [
                        'schemas' => [
                            'default' => [
                                'dir' => resource_path('schemas'),
                            ],
                        ],

                        'engines' => [
                            'default' => [
                                'adapter' => 'typesense://S3CR3T@127.0.0.1:8108',
                            ],
                        ],
                    ];

    .. group-tab:: Mezzio

        When we are using the Mezzio integration package we just need to configure our ``Engine``
        in the ``src/App/src/ConfigProvider.php`` file. The ``Adapter`` is configured via a ``DSN`` like string.

        .. tabs::

            .. group-tab:: Meilisearch

                Use the following configuration to use ``Meilisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cms_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'meilisearch://127.0.0.1:7700',
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }


            .. group-tab:: Algolia

                Use the following configuration to use ``Algolia`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cmsig_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'algolia://' . \getenv('ALGOLIA_APPLICATION_ID') . ':' . \getenv('ALGOLIA_ADMIN_API_KEY'),
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }

            .. group-tab:: Elasticsearch

                Use the following configuration to use ``Elasticsearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cmsig_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'elasticsearch://127.0.0.1:9200',
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }

            .. group-tab:: Opensearch

                Use the following configuration to use ``Opensearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cmsig_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'opensearch://127.0.0.1:9200',
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }

            .. group-tab:: Redisearch

                Use the following configuration to use ``Redisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cmsig_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'redis://127.0.0.1:6379',
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }
            .. group-tab:: Loupe

                Use the following configuration to use ``Loupe`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cmsig_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'loupe://var/indexes',
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }

            .. group-tab:: Solr

                Use the following configuration to use ``Solr`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cmsig_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'solr://127.0.0.1:8983',
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }

            .. group-tab:: Typesense

                Use the following configuration to use ``Typesense`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // src/App/src/ConfigProvider.php

                    class ConfigProvider
                    {
                        public function __invoke(): array
                        {
                            return [
                                // ...
                                'cmsig_seal' => [
                                    'schemas' => [
                                        'default' => [
                                            'dir' => 'config/schemas',
                                        ],
                                    ],

                                    'engines' => [
                                        'default' => [
                                            'adapter' => 'typesense://S3CR3T@127.0.0.1:8108',
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }

    .. group-tab:: Yii

        When we are using the Yii integration package we just need to configure our ``Engine``
        in the ``config/common/params.php`` file. The ``Adapter`` is configured via a ``DSN`` like string.

        .. tabs::

            .. group-tab:: Meilisearch

                Use the following configuration to use ``Meilisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'meilisearch://127.0.0.1:7700',
                                ],
                            ],
                        ],
                    ];


            .. group-tab:: Algolia

                Use the following configuration to use ``Algolia`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'algolia://' . \getenv('ALGOLIA_APPLICATION_ID') . ':' . \getenv('ALGOLIA_ADMIN_API_KEY'),
                                ],
                            ],
                        ],
                    ];

            .. group-tab:: Elasticsearch

                Use the following configuration to use ``Elasticsearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'elasticsearch://127.0.0.1:9200',
                                ],
                            ],
                        ],
                    ];

            .. group-tab:: Opensearch

                Use the following configuration to use ``Opensearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'opensearch://127.0.0.1:9200',
                                ],
                            ],
                        ],
                    ];

            .. group-tab:: Redisearch

                Use the following configuration to use ``Redisearch`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'redis://127.0.0.1:6379',
                                ],
                            ],
                        ],
                    ];

            .. group-tab:: Loupe

                Use the following configuration to use ``Loupe`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'loupe://var/indexes',
                                ],
                            ],
                        ],
                    ];

            .. group-tab:: Solr

                Use the following configuration to use ``Solr`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'solr://127.0.0.1:8983',
                                ],
                            ],
                        ],
                    ];

            .. group-tab:: Typesense

                Use the following configuration to use ``Typesense`` as your default ``Engine`` adapter:

                .. code-block:: php

                    <?php // config/common/params.php

                    return [
                        // ...
                        'cmsig/seal-yii-module' => [
                            'schemas' => [
                                'default' => [
                                    'dir' => 'config/schemas',
                                ],
                            ],

                            'engines' => [
                                'default' => [
                                    'adapter' => 'typesense://S3CR3T@127.0.0.1:8108',
                                ],
                            ],
                        ],
                    ];

Prepare Search Engine
----------------------

If you already have your search engine running you can skip this step. Still we want to
provide here different `docker-compose <https://www.docker.com/products/docker-desktop/>`__ files to get you started quickly with your favorite
search engine.

.. tabs::

    .. group-tab:: Meilisearch

        A instance of `Meilisearch <https://www.meilisearch.com/>`__ can be started with the following docker-compose file:

        .. code-block:: yaml

            # docker-compose.yml

            services:
              meilisearch:
                image: getmeili/meilisearch:v1
                environment:
                  MEILI_ENV: development
                ports:
                  - "7700:7700"
                healthcheck:
                  test: ["CMD-SHELL", "curl --silent --fail localhost:7700/health || exit 1"]
                  interval: 5s
                  timeout: 5s
                  retries: 20
                volumes:
                  - meilisearch-data:/data.ms

            volumes:
              meilisearch-data:

        To start the search engine run the following command:

        .. code-block:: bash

            docker-compose up

        Depending on the service after a few seconds up to a minute the service is ready to use.
        And you can continue with the next step.

    .. group-tab:: Algolia

        As `Algolia <https://www.algolia.com/>`__ is SaaS, there is nothing to run it required. You can create a free account
        at `https://www.algolia.com/users/sign_up <https://www.algolia.com/users/sign_up>`__.
        After Signup you will get an ``ALGOLIA_APPLICATION_ID`` and an ``ALGOLIA_ADMIN_API_KEY``.
        Which you need to configure that your engine adapter configuration will then use them like
        above.

    .. group-tab:: Elasticsearch

        A instance of `Elasticsearch <https://www.elastic.co/what-is/elasticsearch>`__ can be started with the following docker-compose file:

        .. code-block:: yaml

            # docker-compose.yml

            services:
              elasticsearch:
                image: docker.elastic.co/elasticsearch/elasticsearch:8.17.0
                environment:
                  discovery.type: single-node
                  xpack.security.enabled: 'false'
                  cluster.routing.allocation.disk.threshold_enabled: 'false'
                ports:
                  - "9200:9200"
                healthcheck:
                  test: ["CMD-SHELL", "curl --silent --fail localhost:9200/_cluster/health || exit 1"]
                  interval: 5s
                  timeout: 5s
                  retries: 20
                volumes:
                  - elasticsearch-data:/usr/share/elasticsearch/data

            volumes:
                elasticsearch-data:

        To start the search engine run the following command:

        .. code-block:: bash

            docker-compose up

        Depending on the service after a few seconds up to a minute the service is ready to use.
        And you can continue with the next step.

    .. group-tab:: Opensearch

        A instance of `Opensearch <https://opensearch.org/>`__ can be started with the following docker-compose file:

        .. code-block:: yaml

            # docker-compose.yml

            services:
              opensearch:
                image: opensearchproject/opensearch:2
                environment:
                  discovery.type: single-node
                  cluster.routing.allocation.disk.threshold_enabled: 'false'
                  DISABLE_SECURITY_PLUGIN: true
                ports:
                  - "9200:9200"
                healthcheck:
                  test: ["CMD-SHELL", "curl --silent --fail localhost:9200/_cluster/health || exit 1"]
                  interval: 5s
                  timeout: 5s
                  retries: 20
                volumes:
                  - opensearch-data:/usr/share/opensearch/data

            volumes:
              opensearch-data:

        To start the search engine run the following command:

        .. code-block:: bash

            docker-compose up

        Depending on the service after a few seconds up to a minute the service is ready to use.
        And you can continue with the next step.

    .. group-tab:: Redisearch

        A instance of `Redisearch <https://redis.io/docs/stack/search/>`__ can be started with the following docker-compose file.
        The here used `redis/redis-stack` image contains the required ``Redisearch``
        and ``JSON`` modules to run the search engine:

        .. code-block:: yaml

            # docker-compose.yml

            services:
              redis:
                image: redis/redis-stack:7.4.0-v1
                ports:
                  - 6379:6379 # redis server
                  - 8001:8001 # redis insight
                environment:
                  REDIS_ARGS: --requirepass supersecure
                volumes:
                    - redisearch-data:/data

            volumes:
              redisearch-data:

        To start the search engine run the following command:

        .. code-block:: bash

            docker-compose up

        Depending on the service after a few seconds up to a minute the service is ready to use.
        And you can continue with the next step.

    .. group-tab:: Loupe

        As `Loupe <https://github.com/loupe-php/loupe>`__ is PHP based build on top of SQLite, there is no service other service required to use it.
        You just need to make sure that you have the required `sqlite php extension <https://www.php.net/pdo_sqlite>`__` installed. The
        php package manager `composer <https://getcomposer.org/>`__ should already tell you if you are missing the extension.

    .. group-tab:: Solr

        A instance of `Solr <https://solr.apache.org/>`__ can be started with the following docker-compose file.
        It uses the required cloud mode to run the search engine. Running it
        without cloud mode is not supported yet:

        .. code-block:: yaml

            # docker-compose.yml

            services:
              solr:
                image: "solr:9"
                ports:
                 - "8983:8983"
                 - "9983:9983"
                command: solr -f -cloud
                healthcheck:
                  test: ["CMD-SHELL", "curl --silent --fail localhost:8983 || exit 1"]
                  interval: 5s
                  timeout: 5s
                  retries: 20
                environment:
                  SOLR_OPTS: '-Dsolr.disableConfigSetsCreateAuthChecks=true'
                volumes:
                  - solr-data:/var/solr

              zookeeper:
                image: "solr:9"
                depends_on:
                  - "solr"
                network_mode: "service:solr"
                environment:
                  SOLR_OPTS: '-Dsolr.disableConfigSetsCreateAuthChecks=true'
                command: bash -c "set -x; export; wait-for-solr.sh; solr zk -z localhost:9983 upconfig -n default -d /opt/solr/server/solr/configsets/_default; tail -f /dev/null"

            volumes:
              solr-data:

        To start the search engine run the following command:

        .. code-block:: bash

            docker-compose up

        Depending on the service after a few seconds up to a minute the service is ready to use.
        And you can continue with the next step.

    .. group-tab:: Typesense

        A instance of `Typesense <https://typesense.org/>`__ can be started with the following docker-compose file:

        .. code-block:: yaml

            # docker-compose.yml

            services:
              typesense:
                image: typesense/typesense:27.1
                ports:
                  - "8108:8108"
                environment:
                  TYPESENSE_DATA_DIR: /data
                  TYPESENSE_API_KEY: S3CR3T
                healthcheck:
                  test: ["CMD-SHELL", "exit 0"] # TODO currently not working as curl not available: https://github.com/typesense/typesense/issues/441#issuecomment-1383157680
                  interval: 5s
                  timeout: 5s
                  retries: 20
                volumes:
                  - typesense-data:/data

            volumes:
              typesense-data:

        To start the search engine run the following command:

        .. code-block:: bash

            docker-compose up

        Depending on the service after a few seconds up to a minute the service is ready to use.
        And you can continue with the next step.

Create Indexes
--------------

Before you can use the search engine you need to create the indexes.

.. tabs::

    .. group-tab:: Standalone use

        When using the ``Standalone`` version you need to create the ``Indexes``
        in your search engines via the ``Engine`` instance which was created before:

        .. code-block:: php

            <?php

            // create all indexes
            $engine->createSchema();

            // create specific index
            $engine->createIndex('blog');

    .. group-tab:: Laravel

        To create the indexes in Laravel the following artisan command:

        .. code-block:: bash

            # create all indexes
            php artisan cmsig:seal:index-create

            # create specific index
            php artisan cmsig:seal:index-create --index=blog

    .. group-tab:: Symfony

        To create the indexes in Symfony the following console command:

        .. code-block:: bash

            # create all indexes
            bin/console cmsig:seal:index-create

            # create specific index
            bin/console cmsig:seal:index-create --index=blog

    .. group-tab:: Spiral

        To create the indexes in Spiral the following command:

        .. code-block:: bash

            # create all indexes
            php app.php cmsig:seal:index-create

            # create specific index
            php app.php cmsig:seal:index-create --index=blog

    .. group-tab:: Mezzio

        To create the indexes in Mezzio the following command:

        .. code-block:: bash

            # create all indexes
            vendor/bin/laminas cmsig:seal:index-create

            # create specific index
            vendor/bin/laminas cmsig:seal:index-create --index=blog

    .. group-tab:: Yii

        To create the indexes in Yii the following command:

        .. code-block:: bash

            # create all indexes
            ./yii cmsig:seal:index-create

            # create specific index
            ./yii cmsig:seal:index-create --index=blog

Add or Update Documents
-----------------------

A document in SEAL is a associative array following the structure of the defined Schema.
The only required field is the ``IdentifierField`` of the Schema.

To add documents to the search engine you need to use the ``Engine`` instance.
With the following code we can add our first documents to our created index:

.. code-block:: php

    <?php

    class YourService {
        public function __construct(
            private readonly \Schranz\Search\EngineInterface $engine
        ) {
        }

        public function someMethod()
        {
            $this->engine->saveDocument('blog', [
                'id' => 1,
                'title' => 'My first blog post',
                'description' => 'This is the description of my first blog post',
                'tags' => ['UI', 'UX'],
            ]);

            $this->engine->saveDocument('blog', [
                'id' => 3,
                'title' => 'My seconds blog post',
                'content' => 'This is the description of my second blog post',
                'tags' => ['Tech', 'UX'],
            ]);

            $this->engine->saveDocument('blog', [
                'id' => 3,
                'title' => 'My third blog post',
                'content' => 'This is the description of my third blog post',
                'tags' => ['Tech', 'UI'],
            ]);
        }
    }

To update a document you can use the same ``saveDocument`` method with the same identifier.

For all kind of indexing operations have a look at the :doc:`../indexing/index` documentation.

Search Documents
----------------

In this step we will now search for our documents via a search term. This way we
are calling a basic search with a given term to the configured search engine. And
get a result of all documents which match the search term (``first``) and a total count how
many exists in the given index.

.. code-block:: php

    <?php

    class YourService {
        public function __construct(
            private readonly \Schranz\Search\EngineInterface $engine
        ) {
        }

        public function someMethod()
        {
            $result = $this->engine->createSearchBuilder('blog')
                ->addFilter(new \CmsIg\Seal\Search\Condition\SearchCondition('first'))
                ->getResult();

            foreach ($result as $document) {
                // do something with the document
            }

            $total = $result->total();
        }
    }

For all kind of search and filters have a look at the :doc:`../search-and-filters/index` documentation.

Filter Documents
----------------

Not even searching but also filtering the documents are possible. In the following example
we will filter by the ``tags`` field and get all documents which have the tag ``UI``.

.. code-block:: php

    <?php

    class YourService {
        public function __construct(
            private readonly \Schranz\Search\EngineInterface $engine
        ) {
        }

        public function someMethod()
        {
            $result = $this->engine->createSearchBuilder('blog')
                ->addFilter(new \CmsIg\Seal\Search\Condition\EqualCondition('tags', 'UI'));
                ->getResult();

            foreach ($result as $document) {
                // do something with the document
            }

            $total = $result->total();
        }
    }

For all kind of search and filters have a look at the :doc:`../search-and-filters/index` documentation.

Reindex Documents
-----------------

If you have changed the schema or need to index or reindex all your documents
the reindex functionality can be used.

First you need to create a ``ReindexProvider`` providing all your documents.

.. code-block:: php

    <?php

    class BlogReindexProvider implements ReindexProviderInterface
    {
        public function total(): ?int
        {
            return 2;
        }

        public function provide(ReindexConfig $reindexConfig): \Generator
        {
            // use `$reindexConfig->getIdentifiers()` or `$reindexConfig->getDateTimeBoundary()`
            //     to support partial reindexing

            yield [
                'id' => 1,
                'title' => 'Title 1',
                'description' => 'Description 1',
            ];

            yield [
                'id' => 2,
                'title' => 'Title 2',
                'description' => 'Description 2',
            ];
        }

        public static function getIndex(): string
        {
            return 'blog';
        }
    }

After that you can use the ``reindex`` to index all documents:

.. tabs::

    .. group-tab:: Standalone use

        When using the ``Standalone`` version you need to reindex the documents
        in your search engines via the ``Engine`` instance which was created before:

        .. code-block:: php

            <?php

            $reindexProviders = [
                new BlogReindexProvider(),
            ];

            // reindex all indexes
            $engine->reindex($reindexProviders);

            // reindex specific index and drop data before
            $engine->reindex($reindexProviders, 'blog', dropIndex: true);

    .. group-tab:: Laravel

        In Laravel the new created ``ReindexProvider`` need to be tagged correctly:

        .. code-block:: php

            <?php // app/Providers/AppServiceProvider.php

            namespace App\Providers;

            class AppServiceProvider extends \Illuminate\Support\ServiceProvider
            {
                // ...

                public function boot(): void
                {
                    $this->app->singleton(\App\Search\BlogReindexProvider::class, fn () => new \App\Search\BlogReindexProvider());

                    $this->app->tag(\App\Search\BlogReindexProvider::class, 'cmsig_seal.reindex_provider');
                }
            }

        After correctly tagging the ``ReindexProvider`` with ``seal.reindex_provider`` the
        ``cmsig:seal:reindex`` command can be used to index all documents:

        .. code-block:: bash

            # reindex all indexes
            php artisan cmsig:seal:reindex

            # reindex specific index and drop data before
            php artisan cmsig:seal:reindex --index=blog --drop

    .. group-tab:: Symfony

        In Symfony the ``autoconfigure`` feature should already tag the new ``ReindexProvider`` correctly
        with the ``seal.reindex_provider`` tag. If not you can tag it manually:

        .. code-block:: yaml

            # config/services.yaml

            services:
                App\Search\BlogReindexProvider:
                    tags:
                        - { name: cmsig_seal.reindex_provider }

        After correctly tagging the ``ReindexProvider`` use the following command to index all documents:

        .. code-block:: bash

            # reindex all indexes
            bin/console cmsig:seal:reindex

            # reindex specific index and drop data before
            bin/console cmsig:seal:reindex --index=blog --drop

    .. group-tab:: Spiral

        In Spiral the new created ``ReindexProvider`` need to be registered correctly as reindex provider:

        .. code-block:: php

            <?php // app/config/cmsig_seal.php

            return [
                // ...

                'reindex_providers' => [
                    \App\Search\BlogReindexProvider::class,
                ],
            ];

        After correctly registering the ``ReindexProvider`` use the following command to index all documents:

        .. code-block:: bash

            # reindex all indexes
            php app.php cmsig:seal:reindex

            # reindex specific index and drop data before
            php app.php cmsig:seal:reindex --index=blog --drop

    .. group-tab:: Mezzio

        In Mezzio the new created ``ReindexProvider`` need to be registered correctly as reindex provider:

        .. code-block:: php

            <?php // src/App/src/ConfigProvider.php

            class ConfigProvider
            {
                public function __invoke(): array
                {
                    return [
                        // ...
                        'cmsig_seal' => [
                            // ...
                            'reindex_providers' => [
                                \App\Search\BlogReindexProvider::class,
                            ],
                        ],
                    ];
                }

                public function getDependencies(): array
                {
                    return [
                        // ...

                        'invokables' => [
                            \App\Search\BlogReindexProvider::class => \App\Search\BlogReindexProvider::class,
                        ],

                        // ...
                    ];
                }
            }

        After correctly registering the ``ReindexProvider`` use the following command to index all documents:

        .. code-block:: bash

            # reindex all indexes
            vendor/bin/laminas cmsig:seal:reindex

            # reindex specific index and drop data before
            vendor/bin/laminas cmsig:seal:reindex --index=blog --drop

    .. group-tab:: Yii

        In Yii the new created ``ReindexProvider`` need to be registered correctly as reindex provider:

        .. code-block:: php

            <?php // config/common/params.php

            return [
                // ...
                'cmsig/seal-yii-module' => [
                    // ...

                    'reindex_providers' => [
                        \App\Search\BlogReindexProvider::class,
                    ],
                ],
            ];

        After correctly registering the ``ReindexProvider`` use the following command to index all documents:

        .. code-block:: bash

            # reindex all indexes
            ./yii cmsig:seal:reindex

            # reindex specific index and drop data before
            ./yii cmsig:seal:reindex --index=blog --drop

Help needed?
------------

If you need any help or run into any error feel free to use the
`Github Discussions <https://github.com/php-cmsig/search/discussions/categories/q-a>`_
of the main repository to ask any questions. Or check there if
somebody already solved the same problem.

Next Steps
----------

These were the basic steps to get started with the Search Engine Abstraction Layer (**SEAL**).
In the next part of the documentation, we will delve deeper into the :doc:`../schema/index`
and explore the various field definitions. After that, we will a short look at the :doc:`../indexing/index` and then
examine the different conditions of :doc:`../search-and-filters/index` the abstraction provides.
