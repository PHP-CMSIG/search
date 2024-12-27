Index Operations
================

In the :doc:`../getting-started/index` documentation we already saw how we can add documents to our
created Index.

The following shows the basic usage as already shown in the "Getting Started" documentation. Under the
``$this->engine`` variable we assume that you have already injected your created ``Engine`` instance.

Save document
-------------

To add a document to an index we can use the ``saveDocument`` method. The only required field
for the document is the defined ``IdentifierField`` all others are optional and don't need to
be provided or can be null.

.. code-block:: php

    <?php

    $this->engine->saveDocument('blog', [
        'id' => '1',
        'title' => 'My first blog post',
        'description' => 'This is the description of my first blog post',
        'tags' => ['UI', 'UX'],
    ]);

To update a document the same method ``saveDocument`` need to be used with the same ``identifier``
value.

.. note::

    Currently, you can use some kind of normalizer like `symfony/serializer <https://symfony.com/doc/current/components/serializer.html>`__
    to convert an object to an array and back to an object at current state a Document Mapper or ODM package does
    not yet exist. If provided in future it will be part of an own package which make usage of SEAL.
    Example like doctrine/orm using doctrine/dbal. See `ODM issue <https://github.com/php-cmsig/search/issues/81>`__.

Delete document
---------------

To remove a document from an index we can use the ``deleteDocument`` method. It only requires
the name of the index and the identifier of the document which should be removed.

.. code-block:: php

    <?php

    $this->engine->deleteDocument('blog', '1');

Reindex operations
------------------

To reindex documents it is required to create atleast one ReindexProvider for
the index you want to reindex. The ReindexProvider is a class which implements
the ``ReindexProviderInterface`` and provides the documents for your index.

.. code-block:: php

    <?php

    class BlogReindexProvider implements ReindexProviderInterface
    {
        public function total(): ?int
        {
            return 3;
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

            yield [
                'id' => 3,
                'title' => 'Title 3',
                'description' => 'Description 3',
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
            $reindexConfig = \CmsIg\Seal\Reindex\ReindexConfig::create();

            $engine->reindex($reindexProviders);

            // reindex specific index and drop data before
            $reindexConfig = \CmsIg\Seal\Reindex\ReindexConfig::create()
                ->withIndex('blog')
                ->withBulkSize(100)
                ->withDropIndex(true);

            $engine->reindex($reindexProviders, $reindexConfig);

            // reindex specific index since specific date
            $reindexConfig = \CmsIg\Seal\Reindex\ReindexConfig::create()
                ->withIndex('blog')
                ->withBulkSize(100)
                ->withDropIndex(true)
                ->withDateTimeBoundary('-1 day');

            $engine->reindex($reindexProviders, $reindexConfig);

            // reindex specific identifier
            $reindexConfig = \CmsIg\Seal\Reindex\ReindexConfig::create()
                ->withIndex('blog')
                ->withBulkSize(100)
                ->withDropIndex(true)
                ->withIdentifiers([1, 2, 3]);

            $engine->reindex($reindexProviders, $reindexConfig);

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
            php artisan cmsig:seal:reindex --index=blog --drop --bulk-size=100

            # reindex specific index since specific date
            php artisan cmsig:seal:reindex --index=blog --drop --datetime-boundary="-1 day"

            # reindex specific identifier
            php artisan cmsig:seal:reindex --index=blog --identifiers="1,2,3"

    .. group-tab:: Symfony

        In Symfony ``autoconfigure`` feature should already tag the new ``ReindexProvider`` correctly
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
            bin/console cmsig:seal:reindex --index=blog --drop --bulk-size=100

            # reindex specific index since specific date
            bin/console artisan cmsig:seal:reindex --index=blog --drop --datetime-boundary="-1 day"

            # reindex specific identifier
            bin/console artisan cmsig:seal:reindex --index=blog --identifiers="1,2,3"

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
            php app.php cmsig:seal:reindex --index=blog --drop --bulk-size=100

            # reindex specific index since specific date
            php app.php cmsig:seal:reindex --index=blog --drop --datetime-boundary="-1 day"

            # reindex specific identifier
            php app.php cmsig:seal:reindex --index=blog --identifiers="1,2,3"

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
            vendor/bin/laminas cmsig:seal:reindex --index=blog --drop --bulk-size=100

            # reindex specific index since specific date
            vendor/bin/laminas cmsig:seal:reindex --index=blog --drop --datetime-boundary="-1 day"

            # reindex specific identifier
            vendor/bin/laminas cmsig:seal:reindex --index=blog --identifiers="1,2,3"

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
            ./yii cmsig:seal:reindex --index=blog --drop --bulk-size=100

            # reindex specific index since specific date
            ./yii cmsig:seal:reindex --index=blog --drop --datetime-boundary="-1 day"

            # reindex specific identifier
            ./yii cmsig:seal:reindex --index=blog --identifiers="1,2,3"

Bulk operations
---------------

Currently no bulk operations are implemented. Add your opinion to the
`Bulk issue <https://github.com/php-cmsig/search/issues/24>`_
on Github.

Next Steps
----------

After this short introduction about indexing we are able to add and remove our documents from the defined indexes.

In the next chapter, we examine the different conditions of :doc:`../search-and-filters/index` the abstraction provides.
