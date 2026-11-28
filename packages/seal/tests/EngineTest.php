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

namespace CmsIg\Seal\Tests;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\Reindex\DynamicReindexProviderInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Engine::class)]
class EngineTest extends TestCase
{
    /**
     * @return iterable<string, array{string|null, bool, bool, list<string>, list<string>, list<string>}>
     */
    public static function provideDynamicReindexDropConfigurations(): iterable
    {
        yield 'all indexes' => [null, true, true, ['simple', 'complex'], ['simple', 'complex'], ['simple', 'complex']];
        yield 'simple only' => ['simple', true, true, ['simple'], ['simple'], ['simple']];
        yield 'empty complex only' => ['complex', true, true, ['complex'], ['complex'], ['complex']];
        yield 'unknown index' => ['unknown', true, true, [], [], []];
        yield 'drop disabled' => [null, false, true, ['simple', 'complex'], [], []];
        yield 'missing index with drop enabled' => ['simple', true, false, ['simple'], [], ['simple']];
        yield 'missing index with drop disabled' => ['simple', false, false, ['simple'], [], ['simple']];
    }

    /**
     * @param list<string> $selectedIndexes
     * @param list<string> $expectedDrops
     * @param list<string> $expectedCreates
     */
    #[DataProvider('provideDynamicReindexDropConfigurations')]
    public function testDynamicReindexDropSelection(string|null $index, bool $dropIndex, bool $indexExists, array $selectedIndexes, array $expectedDrops, array $expectedCreates): void
    {
        $schema = new Schema([
            'simple' => new Index('simple', ['id' => new IdentifierField('id')]),
            'complex' => new Index('complex', ['uuid' => new IdentifierField('uuid')]),
        ]);
        $config = ReindexConfig::create()->withIndex($index)->withDropIndex($dropIndex);
        $options = ['return_slow_promise_result' => true];

        $schemaManager = new class($indexExists ? $schema->indexes : []) implements SchemaManagerInterface {
            /** @var list<string> */
            private array $droppedIndexes = [];

            /** @var list<string> */
            private array $createdIndexes = [];

            /** @param array<string, Index> $indexes */
            public function __construct(private array $indexes)
            {
            }

            public function existIndex(Index $index): bool
            {
                return isset($this->indexes[$index->name]);
            }

            public function dropIndex(Index $index, array $options = []): TaskInterface|null
            {
                $this->droppedIndexes[] = $index->name;
                unset($this->indexes[$index->name]);

                return ($options['return_slow_promise_result'] ?? false) ? new SyncTask(null) : null;
            }

            public function createIndex(Index $index, array $options = []): TaskInterface|null
            {
                $this->createdIndexes[] = $index->name;
                $this->indexes[$index->name] = $index;

                return ($options['return_slow_promise_result'] ?? false) ? new SyncTask(null) : null;
            }

            /** @return list<string> */
            public function getDroppedIndexes(): array
            {
                return $this->droppedIndexes;
            }

            /** @return list<string> */
            public function getCreatedIndexes(): array
            {
                return $this->createdIndexes;
            }
        };

        $provider = new class() implements DynamicReindexProviderInterface {
            /** @var list<string> */
            public array $totalIndexes = [];

            /** @var list<string> */
            public array $providedIndexes = [];

            public function total(string $index): int
            {
                $this->totalIndexes[] = $index;

                return 'simple' === $index ? 1 : 0;
            }

            /** @return \Generator<array<string, mixed>> */
            public function provide(string $index, ReindexConfig $reindexConfig): \Generator
            {
                $this->providedIndexes[] = $index;

                yield ['id' => 'reindexed'];
            }
        };

        $indexer = new class() implements IndexerInterface {
            /** @var list<array{Index, array<array<string, mixed>>, array<string>, int, array{return_slow_promise_result?: true}}> */
            private array $bulkCalls = [];

            public function save(Index $index, array $document, array $options = []): TaskInterface|null
            {
                throw new \LogicException('Not implemented.');
            }

            public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
            {
                throw new \LogicException('Not implemented.');
            }

            public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
            {
                $this->bulkCalls[] = [$index, [...$saveDocuments], [...$deleteDocumentIdentifiers], $bulkSize, $options];

                return ($options['return_slow_promise_result'] ?? false) ? new SyncTask(null) : null;
            }

            /** @return list<array{Index, array<array<string, mixed>>, array<string>, int, array{return_slow_promise_result?: true}}> */
            public function getBulkCalls(): array
            {
                return $this->bulkCalls;
            }
        };

        $adapter = new class($schemaManager, $indexer) implements AdapterInterface {
            public function __construct(
                private readonly SchemaManagerInterface $schemaManager,
                private readonly IndexerInterface $indexer,
            ) {
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
                throw new \LogicException('Not implemented.');
            }
        };

        $engine = new Engine($adapter, $schema);
        $task = $engine->reindex([$provider], $config, null, $options);
        $task->wait();

        self::assertSame($selectedIndexes, $provider->totalIndexes);
        self::assertSame($expectedDrops, $schemaManager->getDroppedIndexes());
        self::assertSame($expectedCreates, $schemaManager->getCreatedIndexes());
        $expectedProvidedIndexes = \in_array('simple', $selectedIndexes, true) ? ['simple'] : [];
        self::assertSame($expectedProvidedIndexes, $provider->providedIndexes);
        self::assertSame(
            [] !== $expectedProvidedIndexes ? [[$schema->indexes['simple'], [['id' => 'reindexed']], [], $config->getBulkSize(), $options]] : [],
            $indexer->getBulkCalls(),
        );
    }
}
