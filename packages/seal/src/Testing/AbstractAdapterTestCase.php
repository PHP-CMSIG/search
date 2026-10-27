<?php

declare(strict_types=1);

/*
 * This file is part of the CMS-IG SEAL project.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CmsIg\Seal\Testing;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Exception\DocumentNotFoundException;
use CmsIg\Seal\Reindex\DynamicReindexProviderInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\StaticReindexProviderInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\SearchBuilder;
use PHPUnit\Framework\TestCase;

abstract class AbstractAdapterTestCase extends TestCase
{
    protected static AdapterInterface $adapter;

    protected static EngineInterface $engine;

    protected static Schema $schema;

    private static TaskHelper $taskHelper;

    protected function setUp(): void
    {
        self::$taskHelper = new TaskHelper();
    }

    protected function tearDown(): void
    {
        try {
            $task = self::getEngine()->dropSchema(['return_slow_promise_result' => true]);
            $task->wait();
        } catch (\Exception) {
            // ignore eventuell not existing indexes to drop
        }

        self::$taskHelper->waitForAll();
    }

    protected static function getEngine(): EngineInterface
    {
        if (!isset(self::$engine)) {
            self::$schema = TestingHelper::createSchema();

            self::$engine = new Engine(
                self::$adapter,
                self::$schema,
            );
        }

        return self::$engine;
    }

    public function testIndex(): void
    {
        $engine = self::getEngine();
        $indexName = TestingHelper::INDEX_SIMPLE;

        $this->assertFalse($engine->existIndex($indexName));

        $task = $engine->createIndex($indexName, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertTrue($engine->existIndex($indexName));

        $task = $engine->dropIndex($indexName, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertFalse($engine->existIndex($indexName));
    }

    public function testUpdateIndexAddField(): void
    {
        self::getEngine();
        $simpleIndex = self::$schema->indexes[TestingHelper::INDEX_SIMPLE];
        $updatedIndex = new Index($simpleIndex->name, [
            ...$simpleIndex->fields,
            'description' => new Field\TextField('description', filterable: true),
        ]);

        $schemaManager = self::$adapter->getSchemaManager();
        $indexer = self::$adapter->getIndexer();
        $searcher = self::$adapter->getSearcher();

        $schemaManager->createIndex($simpleIndex, ['return_slow_promise_result' => true])->wait();

        try {
            $indexer->save($simpleIndex, ['id' => '1', 'title' => 'Before'], ['return_slow_promise_result' => true])->wait();

            $schemaManager->updateIndex($updatedIndex, ['return_slow_promise_result' => true])->wait();
            $this->assertTrue($schemaManager->existIndex($updatedIndex));
            $this->assertSame(1, $searcher->count($updatedIndex), 'Expected the document saved before updateIndex to be kept.');

            $indexer->save($updatedIndex, ['id' => '2', 'title' => 'After', 'description' => 'Added field'], ['return_slow_promise_result' => true])->wait();
            $indexer->save($updatedIndex, ['id' => '3', 'title' => 'After', 'description' => 'Other value'], ['return_slow_promise_result' => true])->wait();

            $this->assertSame(3, $searcher->count($updatedIndex));

            $schema = new Schema([TestingHelper::INDEX_SIMPLE => $updatedIndex]);

            $search = new SearchBuilder($schema, $searcher);
            $search->index(TestingHelper::INDEX_SIMPLE);
            $search->addFilter(Condition::search('Added'));

            $documents = [...$search->getResult()];
            $this->assertCount(1, $documents);
            $this->assertSame('2', $documents[0]['id']);
            $this->assertSame('Added field', $documents[0]['description']);

            $search = new SearchBuilder($schema, $searcher);
            $search->index(TestingHelper::INDEX_SIMPLE);
            $search->addFilter(Condition::equal('description', 'Other value'));

            $documents = [...$search->getResult()];
            $this->assertCount(1, $documents);
            $this->assertSame('3', $documents[0]['id']);
        } finally {
            // dropSchema in tearDown stops at the first not existing index, so this index is dropped here
            $schemaManager->dropIndex($updatedIndex, ['return_slow_promise_result' => true])->wait();
        }
    }

    public function testUpdateIndexAndSchemaKeepDocuments(): void
    {
        $engine = self::getEngine();
        $engine->createSchema(['return_slow_promise_result' => true])->wait();

        $engine->saveDocument(TestingHelper::INDEX_SIMPLE, ['id' => '1', 'title' => 'Simple'], ['return_slow_promise_result' => true])->wait();
        foreach (TestingHelper::createComplexFixtures() as $document) {
            self::$taskHelper->tasks[] = $engine->saveDocument(TestingHelper::INDEX_COMPLEX, $document, ['return_slow_promise_result' => true]);
        }
        self::$taskHelper->waitForAll();

        $complexCount = $engine->countDocuments(TestingHelper::INDEX_COMPLEX);

        $engine->updateIndex(TestingHelper::INDEX_SIMPLE, ['return_slow_promise_result' => true])->wait();
        $this->assertTrue($engine->existIndex(TestingHelper::INDEX_SIMPLE));
        $this->assertSame(1, $engine->countDocuments(TestingHelper::INDEX_SIMPLE));

        $engine->updateSchema(['return_slow_promise_result' => true])->wait();
        $this->assertSame(1, $engine->countDocuments(TestingHelper::INDEX_SIMPLE));
        $this->assertSame($complexCount, $engine->countDocuments(TestingHelper::INDEX_COMPLEX));
    }

    public function testSchema(): void
    {
        $engine = self::getEngine();
        $indexes = self::$schema->indexes;

        $task = $engine->createSchema(['return_slow_promise_result' => true]);
        $task->wait();

        foreach (\array_keys($indexes) as $index) {
            $this->assertTrue($engine->existIndex($index));
        }

        $task = $engine->dropSchema(['return_slow_promise_result' => true]);
        $task->wait();

        foreach (\array_keys($indexes) as $index) {
            $this->assertFalse($engine->existIndex($index));
        }
    }

    public function testDocument(): void
    {
        $engine = self::getEngine();
        $task = self::getEngine()->createSchema(['return_slow_promise_result' => true]);
        $task->wait();

        $documents = TestingHelper::createComplexFixtures();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = $engine->saveDocument(TestingHelper::INDEX_COMPLEX, $document, ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();

        $loadedDocuments = [];
        foreach ($documents as $document) {
            $loadedDocuments[] = $engine->getDocument(TestingHelper::INDEX_COMPLEX, $document['uuid']);
        }

        $this->assertCount(
            \count($documents),
            $loadedDocuments,
        );

        foreach ($loadedDocuments as $key => $loadedDocument) {
            $expectedDocument = $documents[$key];

            $this->assertSame($expectedDocument, $loadedDocument, 'Expected the loaded document to be the same as the saved document (' . $expectedDocument['uuid'] . ').');
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = $engine->deleteDocument(TestingHelper::INDEX_COMPLEX, $document['uuid'], ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();

        foreach ($documents as $document) {
            $exceptionThrown = false;

            try {
                $engine->getDocument(TestingHelper::INDEX_COMPLEX, $document['uuid']);
            } catch (DocumentNotFoundException) {
                $exceptionThrown = true;
            }

            $this->assertTrue(
                $exceptionThrown,
                'Expected the exception "DocumentNotFoundException" to be thrown.',
            );
        }
    }

    public function testReindex(): void
    {
        $engine = self::getEngine();
        $task = self::getEngine()->createSchema(['return_slow_promise_result' => true]);
        $task->wait();

        $removedDocument = [
            'uuid' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
            'title' => 'Removed Document',
        ];

        $documents = [
            ...TestingHelper::createComplexFixtures(),
            $removedDocument,
        ];

        $reindexProvider = $this->createReindexProvider($documents);
        $engine->reindex([$reindexProvider], new ReindexConfig(), null, ['return_slow_promise_result' => true])->wait(); // @phpstan-ignore-line

        $expectedDocuments = [];
        foreach ($documents as $document) {
            $expectedDocuments[] = $engine->getDocument(TestingHelper::INDEX_COMPLEX, $document['uuid']);
        }

        unset($expectedDocuments[\count($expectedDocuments) - 1]);

        $this->assertCount(
            \count($documents) - 1,
            $expectedDocuments,
        );

        $reindexProvider = $this->createReindexProvider($expectedDocuments);
        $reindexConfig = (new ReindexConfig())
            ->withIndex(TestingHelper::INDEX_COMPLEX)
            ->withIdentifiers(
                \array_map(
                    static fn ($document) => $document['uuid'],
                    $documents,
                ),
            );
        $counter = 0;
        // @phpstan-ignore-next-line arguments.count
        $engine->reindex([$reindexProvider], $reindexConfig, static function (string $index, int $total, int $count) use (&$counter) {
            $counter = $count;
        }, ['return_slow_promise_result' => true])->wait(); // @phpstan-ignore-line method.nonObject

        $this->assertSame(4, $counter);

        $exception = null;
        try {
            $engine->getDocument(TestingHelper::INDEX_COMPLEX, '3fa85f64-5717-4562-b3fc-2c963f66afa6');
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(DocumentNotFoundException::class, $exception);

        foreach ($expectedDocuments as $document) {
            \assert(\is_string($document['uuid']), 'UUID is not a string');
            self::$taskHelper->tasks[] = $engine->deleteDocument(TestingHelper::INDEX_COMPLEX, $document['uuid'], ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();
    }

    public function testDynamicReindex(): void
    {
        $engine = self::getEngine();
        $task = self::getEngine()->createSchema(['return_slow_promise_result' => true]);
        $task->wait();

        $removedDocument = [
            'uuid' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
            'title' => 'Removed Document',
        ];

        $documents = [
            ...TestingHelper::createComplexFixtures(),
            $removedDocument,
        ];

        $reindexProvider = $this->createDynamicReindexProvider($documents);
        // @phpstan-ignore-next-line arguments.count
        $engine->reindex([$reindexProvider], new ReindexConfig(), null, ['return_slow_promise_result' => true])->wait();

        $expectedDocuments = [];
        foreach ($documents as $document) {
            $expectedDocuments[] = $engine->getDocument(TestingHelper::INDEX_COMPLEX, $document['uuid']);
        }

        unset($expectedDocuments[\count($expectedDocuments) - 1]);

        $this->assertCount(
            \count($documents) - 1,
            $expectedDocuments,
        );

        $reindexProvider = $this->createDynamicReindexProvider($expectedDocuments);
        $reindexConfig = (new ReindexConfig())
            ->withIndex(TestingHelper::INDEX_COMPLEX)
            ->withIdentifiers(
                \array_map(
                    static fn ($document) => $document['uuid'],
                    $documents,
                ),
            );
        $counter = 0;
        // @phpstan-ignore-next-line arguments.count
        $engine->reindex([$reindexProvider], $reindexConfig, static function (string $index, int $total, int $count) use (&$counter) {
            $counter = $count;
        }, ['return_slow_promise_result' => true])->wait(); // @phpstan-ignore-line method.nonObject

        $this->assertSame(4, $counter);

        $exception = null;
        try {
            $engine->getDocument(TestingHelper::INDEX_COMPLEX, '3fa85f64-5717-4562-b3fc-2c963f66afa6');
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(DocumentNotFoundException::class, $exception);

        foreach ($expectedDocuments as $document) {
            \assert(\is_string($document['uuid']), 'UUID is not a string');
            self::$taskHelper->tasks[] = $engine->deleteDocument(TestingHelper::INDEX_COMPLEX, $document['uuid'], ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();

        $reindexConfig = (new ReindexConfig())
            ->withIndex(TestingHelper::INDEX_SIMPLE)
            ->withIdentifiers(
                \array_map(
                    static fn ($document) => $document['uuid'],
                    $documents,
                ),
            );
        $counter = 0;
        // @phpstan-ignore-next-line arguments.count
        $engine->reindex([$reindexProvider], $reindexConfig, static function (string $index, int $total, int $count) use (&$counter) {
            $counter = $count;
        }, ['return_slow_promise_result' => true])->wait(); // @phpstan-ignore-line method.nonObject

        $this->assertSame(0, $counter);
    }

    public function testCountDocuments(): void
    {
        $engine = self::getEngine();
        $task = self::getEngine()->createSchema(['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertSame(0, $engine->countDocuments(TestingHelper::INDEX_COMPLEX));

        $documents = TestingHelper::createComplexFixtures();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = $engine->saveDocument(TestingHelper::INDEX_COMPLEX, $document, ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();

        $this->assertSame(4, $engine->countDocuments(TestingHelper::INDEX_COMPLEX));

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = $engine->deleteDocument(TestingHelper::INDEX_COMPLEX, $document['uuid'], ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();
    }

    /**
     * @param array<array<string, mixed>> $documents
     */
    private function createReindexProvider(array $documents): StaticReindexProviderInterface
    {
        return new class($documents) implements StaticReindexProviderInterface {
            /**
             * @param array<array<string, mixed>> $documents
             */
            public function __construct(private readonly array $documents)
            {
            }

            public function total(): int
            {
                return 4;
            }

            public function provide(ReindexConfig $reindexConfig): \Generator
            {
                $documents = $this->documents;

                foreach ($documents as $document) {
                    yield $document;
                }
            }

            public function getIndexName(): string
            {
                return TestingHelper::INDEX_COMPLEX;
            }
        };
    }

    /**
     * @param array<array<string, mixed>> $documents
     */
    private function createDynamicReindexProvider(array $documents): DynamicReindexProviderInterface
    {
        return new class($documents) implements DynamicReindexProviderInterface {
            /**
             * @param array<array<string, mixed>> $documents
             */
            public function __construct(private readonly array $documents)
            {
            }

            public function total(string $index): int
            {
                if (TestingHelper::INDEX_COMPLEX !== $index) {
                    return 0;
                }

                return 4;
            }

            public function provide(string $index, ReindexConfig $reindexConfig): \Generator
            {
                if (TestingHelper::INDEX_COMPLEX !== $index) {
                    return;
                }

                $documents = $this->documents;

                foreach ($documents as $document) {
                    yield $document;
                }
            }
        };
    }
}
