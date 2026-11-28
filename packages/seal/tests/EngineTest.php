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
use CmsIg\Seal\Engine;
use CmsIg\Seal\Reindex\DynamicReindexProviderInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Task\SyncTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Engine::class)]
class EngineTest extends TestCase
{
    /**
     * @return iterable<string, array{string|null, bool, bool, list<string>, list<string>}>
     */
    public static function provideDynamicReindexDropConfigurations(): iterable
    {
        yield 'all indexes' => [null, true, true, ['simple', 'complex'], ['drop:simple', 'create:simple', 'bulk:simple', 'drop:complex', 'create:complex']];
        yield 'simple only' => ['simple', true, true, ['simple'], ['drop:simple', 'create:simple', 'bulk:simple']];
        yield 'empty complex only' => ['complex', true, true, ['complex'], ['drop:complex', 'create:complex']];
        yield 'unknown index' => ['unknown', true, true, [], []];
        yield 'drop disabled' => [null, false, true, ['simple', 'complex'], ['bulk:simple']];
        yield 'missing index with drop enabled' => ['simple', true, false, ['simple'], ['create:simple', 'bulk:simple']];
        yield 'missing index with drop disabled' => ['simple', false, false, ['simple'], ['create:simple', 'bulk:simple']];
    }

    /**
     * @param list<string> $selectedIndexes
     * @param list<string> $expectedEvents
     */
    #[DataProvider('provideDynamicReindexDropConfigurations')]
    public function testDynamicReindexDropSelection(string|null $index, bool $dropIndex, bool $indexExists, array $selectedIndexes, array $expectedEvents): void
    {
        $schema = new Schema([
            'simple' => new Index('simple', ['id' => new IdentifierField('id')]),
            'complex' => new Index('complex', ['uuid' => new IdentifierField('uuid')]),
        ]);
        $config = ReindexConfig::create()->withIndex($index)->withDropIndex($dropIndex);
        $options = ['return_slow_promise_result' => true];

        $events = [];
        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $schemaManager->method('existIndex')->willReturnCallback(
            static function (Index $schemaIndex) use ($schema, $selectedIndexes, $indexExists): bool {
                self::assertContains($schemaIndex->name, $selectedIndexes);
                self::assertSame($schema->indexes[$schemaIndex->name], $schemaIndex);

                return $indexExists;
            },
        );
        $schemaManager->expects(self::exactly($dropIndex && $indexExists ? \count($selectedIndexes) : 0))
            ->method('dropIndex')
            ->willReturnCallback(static function (Index $schemaIndex, array $taskOptions) use (&$events, $options): SyncTask {
                self::assertSame($options, $taskOptions);
                $events[] = 'drop:' . $schemaIndex->name;

                return new SyncTask(null);
            });
        $schemaManager->expects(self::exactly($dropIndex || !$indexExists ? \count($selectedIndexes) : 0))
            ->method('createIndex')
            ->willReturnCallback(static function (Index $schemaIndex, array $taskOptions) use (&$events, $options): SyncTask {
                self::assertSame($options, $taskOptions);
                $events[] = 'create:' . $schemaIndex->name;

                return new SyncTask(null);
            });

        $provider = new class() implements DynamicReindexProviderInterface {
            /** @var list<string> */
            public array $totalIndexes = [];

            public function total(string $index): int
            {
                $this->totalIndexes[] = $index;

                return 'simple' === $index ? 1 : 0;
            }

            /** @return \Generator<array<string, mixed>> */
            public function provide(string $index, ReindexConfig $reindexConfig): \Generator
            {
                TestCase::assertSame('simple', $index);

                yield ['id' => 'reindexed'];
            }
        };

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects(self::exactly(\in_array('simple', $selectedIndexes, true) ? 1 : 0))
            ->method('bulk')
            ->willReturnCallback(static function (Index $schemaIndex, iterable $documents, iterable $identifiers, int $bulkSize, array $taskOptions) use ($schema, $config, $options, &$events): SyncTask {
                self::assertSame($schema->indexes['simple'], $schemaIndex);
                self::assertSame([['id' => 'reindexed']], [...$documents]);
                self::assertSame([], [...$identifiers]);
                self::assertSame($config->getBulkSize(), $bulkSize);
                self::assertSame($options, $taskOptions);
                $events[] = 'bulk:simple';

                return new SyncTask(null);
            });

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('getSchemaManager')->willReturn($schemaManager);
        $adapter->method('getIndexer')->willReturn($indexer);
        $adapter->expects(self::never())->method('getSearcher');

        $engine = new Engine($adapter, $schema);
        $task = $engine->reindex([$provider], $config, null, $options);
        $task->wait();

        self::assertSame($selectedIndexes, $provider->totalIndexes);
        self::assertSame($expectedEvents, $events);
    }
}
