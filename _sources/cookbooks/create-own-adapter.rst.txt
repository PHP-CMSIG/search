Create own Adapter
==================

In this part of the documentation describes how to create an own adapter.
Before you start with it let us know via an `issue <https://github.com/php-cmsig/search>`__
if it maybe an Adapter which make sense to add to the SEAL core and we can work together to get it in it.

Install dependencies
--------------------

To create your own Adapter you need atleast the SEAL composer package:

.. code-block:: bash

    composer require cmsig/seal

The project already ships a test suite based on PHPUnit to use it you need to install PHPUnit:

    composer require phpunit/phpunit:"^9.6"

Create Basic Classes
--------------------

An own Adapter depends on the following classes which are responsible for all different operations:

- ``Adapter``
   - ``SchemaManager`` (create and drop indexes)
   - ``Indexer`` (save and delete documents)
   - ``Searcher`` (search documents)
- ``AdapterFactory``

Create Adapter
~~~~~~~~~~~~~~

The Adapter is the main entry point for the own Adapter and provides access to the ``SchemaManager``, ``Indexer`` and ``Searcher``.

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter;

    use Some\Third\Party\Client;
    use CmsIg\Seal\Adapter\AdapterInterface;
    use CmsIg\Seal\Adapter\IndexerInterface;
    use CmsIg\Seal\Adapter\SchemaManagerInterface;
    use CmsIg\Seal\Adapter\SearcherInterface;

    final class MyAdapter implements AdapterInterface
    {
        private readonly SchemaManagerInterface $schemaManager;

        private readonly IndexerInterface $indexer;

        private readonly SearcherInterface $searcher;

        public function __construct(
            Client $client,
            ?SchemaManagerInterface $schemaManager = null,
            ?IndexerInterface $indexer = null,
            ?SearcherInterface $searcher = null,
        ) {
            $this->schemaManager = $schemaManager ?? new MySchemaManager($client);
            $this->indexer = $indexer ?? new MyIndexer($client);
            $this->searcher = $searcher ?? new MySearcher($client);
        }

        public function getSchemaManager(): SchemaManagerInterface
        {
            return $this->schemaManager;
        }

        public function getIndexer(): IndexerInterface
        {
            return $this->indexer;
        }

        public function getSearcher(): SearcherInterface
        {
            return $this->searcher;
        }
    }

Create SchemaManager
~~~~~~~~~~~~~~~~~~~~

The ``SchemaManager`` is responsible for creating and dropping indexes.

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter;

    use Some\Third\Party\Client;
    use CmsIg\Seal\Adapter\SchemaManagerInterface;
    use CmsIg\Seal\Schema\Index;
    use CmsIg\Seal\Task\AsyncTask;
    use CmsIg\Seal\Task\TaskInterface;

    final class MySchemaManager implements SchemaManagerInterface
    {
        public function __construct(
            private readonly Client $client,
        ) {
        }

        public function existIndex(Index $index): bool
        {
            // TODO we will tackle this later
        }

        public function dropIndex(Index $index, array $options = []): ?TaskInterface
        {
            // TODO we will tackle this later
        }

        public function createIndex(Index $index, array $options = []): ?TaskInterface
        {
            // TODO we will tackle this later
        }
    }

Create Indexer
~~~~~~~~~~~~~~

The ``Indexer`` is responsible for saving and deleting documents.

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter;

    use Some\Third\Party\Client;
    use CmsIg\Seal\Adapter\IndexerInterface;
    use CmsIg\Seal\Marshaller\Marshaller;
    use CmsIg\Seal\Schema\Index;
    use CmsIg\Seal\Task\AsyncTask;
    use CmsIg\Seal\Task\TaskInterface;

    final class MyIndexer implements IndexerInterface
    {
        private readonly Marshaller $marshaller;

        public function __construct(
            private readonly Client $client,
        ) {
            $this->marshaller = new Marshaller();
        }

        public function save(Index $index, array $document, array $options = []): ?TaskInterface
        {
            // TODO we will tackle this later
        }

        public function delete(Index $index, string $identifier, array $options = []): ?TaskInterface
        {
            // TODO we will tackle this later
        }
    }

The ``Marshaller`` is responsible for converting the document into an easier Format to index documents.
There exists 2 ``Marshaller``the ``Marshaller`` which keeps nested objects and the ``FlattenMarshaller``
which flatten nested objects to the root by using ``.`` as divider.

Create Searcher
~~~~~~~~~~~~~~~

The ``Searcher`` is responsible for searching documents.

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter;

    use Some\Third\Party\Client;
    use CmsIg\Seal\Adapter\SearcherInterface;
    use CmsIg\Seal\Marshaller\Marshaller;
    use CmsIg\Seal\Schema\Index;
    use CmsIg\Seal\Search\Condition;
    use CmsIg\Seal\Search\Result;
    use CmsIg\Seal\Search\Search;

    final class MySearcher implements SearcherInterface
    {
        private readonly Marshaller $marshaller;

        public function __construct(
            private readonly Client $client,
        ) {
            $this->marshaller = new Marshaller();
        }

        public function search(Search $search): Result
        {
            // TODO we will tackle this later
        }
    }

The ``Searcher`` requires the same Marshaller as the ``Indexer`` to convert the document back to the original format.

Create AdapterFactory
~~~~~~~~~~~~~~~~~~~~~

The ``AdapterFactory`` is responsible for creating the ``Adapter`` mostly used by
integrations into Frameworks Dependency Injection container and constructing the
``Adapter`` via a DSN string.

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter;

    use Some\Third\Party\Client;
    use Psr\Container\ContainerInterface;
    use CmsIg\Seal\Adapter\AdapterFactoryInterface;
    use CmsIg\Seal\Adapter\AdapterInterface;

    /**
     * @experimental
     */
    final class MyAdapterFactory implements AdapterFactoryInterface
    {
        public function __construct(
            private readonly ?ContainerInterface $container = null,
        ) {
        }

        public function createAdapter(array $dsn): AdapterInterface
        {
            $client = $this->createClient($dsn);

            return new MyAdapter($client);
        }

        /**
         * @internal
         *
         * @param array{
         *     host: string,
         *     port?: int,
         *     user?: string,
         *     pass?: string,
         * } $dsn
         */
        public function createClient(array $dsn): SearchClient
        {
            if ('' === $dsn['host']) {
                $client = $this->container?->get(Client::class);

                return $client;
            }

            $client = new Client(
                $dsn['host'] . ':' . ($dsn['port'] ?? 9200),+
                $dsn['user'] ?? '',
                $pass = $dsn['pass'] ?? '',
            );

            return $client;
        }

        public static function getName(): string
        {
            return 'my';
        }
    }

Creating Tests
--------------

The easiest way to create an own Adapter is following TDD (Test Driven Development) and use the shipped TestSuite.

For this we will create the following new files:

 - ``tests/MySchemaManagerTest.php``
 - ``tests/MyAdapterTest.php``
 - ``tests/MyIndexerTest.php``
 - ``tests/MySearcherTest.php``

For most adapters they require a Third Party client to make constructing of that Client
easier we will create a ``ClientHelper`` class in our new test suite.

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter\Tests;

    use Some\Third\Party\Client;

    final class ClientHelper
    {
        private static ?Client $client = null;

        public static function getClient(): Client
        {
            if (!self::$client instanceof Client) {
                self::$client = new Client($_ENV['MY_OWN_HOST'] ?? '127.0.0.1:7700');
            }

            return self::$client;
        }
    }

SchemaManagerTest
~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter\Tests;

    use My\Own\Adapter\MySchemaManager;
    use CmsIg\Seal\Testing\AbstractSchemaManagerTestCase;
    use CmsIg\Seal\Testing\TestingHelper;

    class MySchemaManagerTest extends AbstractSchemaManagerTestCase
    {
        private static Client $client;

        public static function setUpBeforeClass(): void
        {
            self::$client = ClientHelper::getClient();
            self::$schemaManager = new MySchemaManager(self::$client);

            parent::setUpBeforeClass();
        }
    }

MyAdapterTest
~~~~~~~~~~~~~

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter\Tests;

    use My\Own\Adapter\MyAdapter;
    use CmsIg\Seal\Testing\AbstractAdapterTestCase;

    class MyAdapterTest extends AbstractAdapterTestCase
    {
        public static function setUpBeforeClass(): void
        {
            $client = ClientHelper::getClient();
            self::$adapter = new MyAdapter($client);

            parent::setUpBeforeClass();
        }
    }

MyIndexerTest
~~~~~~~~~~~~~

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter\Tests;

    use My\Own\Adapter\MyAdapter;
    use CmsIg\Seal\Testing\AbstractIndexerTestCase;

    class MyIndexerTest extends AbstractIndexerTestCase
    {
        public static function setUpBeforeClass(): void
        {
            $client = ClientHelper::getClient();
            self::$adapter = new MyAdapter($client);

            parent::setUpBeforeClass();
        }
    }

MySearcherTest
~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter\Tests;

    use My\Own\Adapter\MyAdapter;
    use CmsIg\Seal\Testing\AbstractSearcherTestCase;

    class MySearcherTest extends AbstractSearcherTestCase
    {
        public static function setUpBeforeClass(): void
        {
            $client = ClientHelper::getClient();
            self::$adapter = new MyAdapter($client);

            parent::setUpBeforeClass();
        }

        /**
         * @doesNotPerformAssertions
         */
        public function testFindMultipleIndexes(): void
        {
            $this->markTestSkipped('Not supported by MyOwnSearchEngine: https://github.com/.../.../issues/28');
        }
    }

Implementing Logic
------------------

Now we can begin to implement the logic for our own Adapter.

Implementing SchemaManager
~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``SchemaManager`` is the required way to start to implement as all other Services
depending on it that it works.

The SchemaManager is responsible for create and drop indexes and configure the Index
fields correctly based on their type and defined options. How this can be achieved
is different from Search Engine to Search Engine.

Read the :doc:`../schema/index` documentation to get an overview of the different field types which exists.

.. code-block:: php

    vendor/bin/phpunit --filter="SchemaManagerTest"

Now you can step by step implementing the SchemaManager methods.

Examples for different ``SchemaManager`` can be found in the official Repository:

- `AlgoliaSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-algolia-adapter/src/AlgoliaSchemaManager.php>`__
- `ElasticsearchSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-elasticsearch-adapter/src/ElasticsearchSchemaManager.php>`__
- `OpensearchSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-opensearch-adapter/src/OpensearchSchemaManager.php>`__
- `MeilisearchSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-meilisearch-adapter/src/MeilisearchSchemaManager.php>`__
- `LoupeSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-loupe-adapter/src/LoupeSchemaManager.php>`__
- `RediSearchSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-redisearch-adapter/src/RediSearchSchemaManager.php>`__
- `SolrSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-solr-adapter/src/SolrSchemaManager.php>`__
- `TypesenseSchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-typesense-adapter/src/TypesenseSchemaManager.php>`__
- `MemorySchemaManager <https://github.com/php-cmsig/search/blob/0.1/packages/seal-memory-adapter/src/MemorySchemaManager.php>`__

Implementing the Indexer
~~~~~~~~~~~~~~~~~~~~~~~~

After the ``SchemaManager`` works like expected we will continue with the ``Indexer``.
This is responsible to save and delete documents from the Search Engine. How this can be achieved
is different from Search Engine to Search Engine.

.. note::

    The ``IndexerTest`` requires a basic ``Searcher`` implementation to work. See next ``Implementing the Searcher`` section.

Examples for different ``Indexer`` can be found in the official Repository:

- `AlgoliaIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-algolia-adapter/src/AlgoliaIndexer.php>`__
- `ElasticsearchIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-elasticsearch-adapter/src/ElasticsearchIndexer.php>`__
- `OpensearchIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-opensearch-adapter/src/OpensearchIndexer.php>`__
- `MeilisearchIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-meilisearch-adapter/src/MeilisearchIndexer.php>`__
- `LoupeIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-loupe-adapter/src/LoupeIndexer.php>`__
- `RediSearchIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-redisearch-adapter/src/RediSearchIndexer.php>`__
- `SolrIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-solr-adapter/src/SolrIndexer.php>`__
- `TypesenseIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-typesense-adapter/src/TypesenseIndexer.php>`__
- `MemoryIndexer <https://github.com/php-cmsig/search/blob/0.1/packages/seal-memory-adapter/src/MemorySchemaManager.php>`__

Implementing the Searcher
~~~~~~~~~~~~~~~~~~~~~~~~~

A Basic ``Searcher`` implementation is required that we can test the ``Indexer`` as we need
a way to load a document by its identifier. How this can be achieved is different from
Search Engine to Search Engine. A common way is the following example:

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace My\Own\Adapter;

    use Some\Third\Party\Client;
    use CmsIg\Seal\Adapter\SearcherInterface;
    use CmsIg\Seal\Marshaller\Marshaller;
    use CmsIg\Seal\Schema\Index;
    use CmsIg\Seal\Search\Condition;
    use CmsIg\Seal\Search\Result;
    use CmsIg\Seal\Search\Search;

    final class MySearcher implements SearcherInterface
    {
        private readonly Marshaller $marshaller;

        public function __construct(
            private readonly Client $client,
        ) {
            $this->marshaller = new Marshaller();
        }

        public function search(Search $search): Result
        {
            // optimized single document query
            if (
                1 === \count($search->filters)
                && $search->filters[0] instanceof Condition\IdentifierCondition
                && 0 === $search->offset
                && 1 === $search->limit
            ) {
                $singleDocumentIndexName = $search->index->name;
                $singleDocumentIdentifier = $search->filters[0]->identifier;

                try {
                    $data = $this->client->index($singleDocumentIndexName)->getDocument($singleDocumentIdentifier);
                } catch (ApiException $e) {
                    if (404 !== $e->httpStatus) {
                        throw $e;
                    }

                    return new Result(
                        $this->hitsToDocuments($search->index, []),
                        0,
                    );
                }

                return new Result(
                    $this->hitsToDocuments($search->index, [$data]),
                    1,
                );
            }

            // TODO
        }

        /**
         * @param Index $index
         * @param iterable<array<string, mixed>> $hits
         *
         * @return \Generator<int, array<string, mixed>>
         */
        private function hitsToDocuments(Index $index, iterable $hits): \Generator
        {
            foreach ($hits as $hit) {
                yield $this->marshaller->unmarshall($index->fields, $hit);
            }
        }
    }

.. code-block:: php

    vendor/bin/phpunit --filter="IndexerTest"

If that works like expected we can continue with the ``SearcherTest``:

.. code-block:: php

    vendor/bin/phpunit --filter="SearcherTest"

This is the most difficult part to implement all different conditions. How this can be achieved
is different from Search Engine to Search Engine.

Read the :doc:`../search-and-filters/index` documentation to get an overview of the different searches and filters which exists.

Examples for different ``Searcher`` can be found in the official Repository:

- `AlgoliaSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-algolia-adapter/src/AlgoliaSearcher.php>`__
- `ElasticsearchSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-elasticsearch-adapter/src/ElasticsearchSearcher.php>`__
- `OpensearchSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-opensearch-adapter/src/OpensearchSearcher.php>`__
- `MeilisearchSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-meilisearch-adapter/src/MeilisearchSearcher.php>`__
- `LoupeSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-loupe-adapter/src/LoupeSearcher.php>`__
- `RediSearchSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-redisearch-adapter/src/RediSearchSearcher.php>`__
- `SolrSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-solr-adapter/src/SolrSearcher.php>`__
- `TypesenseSearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-typesense-adapter/src/TypesenseSearcher.php>`__
- `MemorySearcher <https://github.com/php-cmsig/search/blob/0.1/packages/seal-memory-adapter/src/MemorySchemaManager.php>`__

Conclusion
----------

If all tests are green you can be sure that your implementation works like expected.
You can publish your own adapter also as a composer package if you want to make it public available.

Tag the packagist package with `seal-adapter <https://packagist.org/search/?tags=seal-adapter>`__
and your use the GitHub Topic `seal-php-adapter <https://github.com/topics/seal-php-adapter>`__.

This way also other can easily find your own created adapter.
