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
    Example like doctrine/orm using doctrine/dbal. See `ODM issue <https://github.com/schranz-search/schranz-search/issues/81>`__.

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

        public function provide(): \Generator
        {
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

                    $this->app->tag(\App\Search\BlogReindexProvider::class, 'seal.reindex_provider');
                }
            }

        After correctly tagging the ``ReindexProvider`` with ``seal.reindex_provider`` the
        ``seal:reindex`` command can be used to index all documents:

        .. code-block:: bash

            # reindex all indexes
            php artisan seal:reindex

            # reindex specific index and drop data before
            php artisan seal:reindex --index=blog --drop

    .. group-tab:: Symfony

        In Symfony ``autoconfigure`` feature should already tag the new ``ReindexProvider`` correctly
        with the ``seal.reindex_provider`` tag. If not you can tag it manually:

        .. code-block:: yaml

            # config/services.yaml

            services:
                App\Search\BlogReindexProvider:
                    tags:
                        - { name: seal.reindex_provider }

        After correctly tagging the ``ReindexProvider`` use the following command to index all documents:

        .. code-block:: bash

            # reindex all indexes
            bin/console seal:reindex

            bin/console seal:reindex --index=blog --drop

    .. group-tab:: Spiral

        In Spiral the new created ``ReindexProvider`` need to be registered correctly as reindex provider:

        .. code-block:: php

            <?php // app/config/seal.php

            return [
                // ...

                'reindex_providers' => [
                    \App\Search\BlogReindexProvider::class,
                ],
            ];

        After correctly registering the ``ReindexProvider`` use the following command to index all documents:

        .. code-block:: bash

            # reindex all indexes
            php app.php seal:reindex

            php app.php seal:reindex --index=blog --drop

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
                        'seal' => [
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
            vendor/bin/laminas seal:reindex

            vendor/bin/laminas seal:reindex --index=blog --drop

    .. group-tab:: Yii

        In Yii the new created ``ReindexProvider`` need to be registered correctly as reindex provider:

        .. code-block:: php

            <?php // config/common/params.php

            return [
                // ...
                'schranz-search/yii-module' => [
                    // ...

                    'reindex_providers' => [
                        \App\Search\BlogReindexProvider::class,
                    ],
                ],
            ];

        After correctly registering the ``ReindexProvider`` use the following command to index all documents:

        .. code-block:: bash

            # reindex all indexes
            ./yii seal:reindex

            ./yii seal:reindex --index=blog --drop

Bulk operations
---------------

Currently no bulk operations are implemented. Add your opinion to the
`Bulk issue <https://github.com/schranz-search/schranz-search/issues/24>`_
on Github.

Next Steps
----------

After this short introduction about indexing we are able to add and remove our documents from the defined indexes.

In the next chapter, we examine the different conditions of :doc:`../search-and-filters/index` the abstraction provides.
