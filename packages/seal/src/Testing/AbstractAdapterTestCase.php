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
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Schema\Schema;
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
    private function createReindexProvider(array $documents): ReindexProviderInterface
    {
        return new class($documents) implements ReindexProviderInterface {
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

            public static function getIndex(): string
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
