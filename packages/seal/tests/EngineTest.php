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
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Testing\TestingHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Engine::class)]
#[CoversClass(\CmsIg\Seal\Reindex\DynamicReindexProviderInterface::class)]
final class EngineTest extends TestCase
{
    public function testReindexSupportsDynamicProviderWithoutStaticIndex(): void
    {
        $schema = TestingHelper::createSchema();
        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $schemaManager->method('existIndex')->willReturn(true);

        $searcher = $this->createMock(SearcherInterface::class);

        $bulkCalls = [];
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->method('bulk')->willReturnCallback(
            static function (Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize, array $options) use (&$bulkCalls): null {
                $saveDocumentsArray = [];
                foreach ($saveDocuments as $saveDocument) {
                    $saveDocumentsArray[] = $saveDocument;
                }

                $deleteDocumentIdentifiersArray = [];
                foreach ($deleteDocumentIdentifiers as $deleteDocumentIdentifier) {
                    $deleteDocumentIdentifiersArray[] = $deleteDocumentIdentifier;
                }

                $bulkCalls[] = [
                    'index' => $index->name,
                    'saveDocuments' => $saveDocumentsArray,
                    'deleteDocumentIdentifiers' => $deleteDocumentIdentifiersArray,
                    'bulkSize' => $bulkSize,
                    'options' => $options,
                ];

                return null;
            },
        );

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('getSchemaManager')->willReturn($schemaManager);
        $adapter->method('getIndexer')->willReturn($indexer);
        $adapter->method('getSearcher')->willReturn($searcher);

        $engine = new Engine($adapter, $schema);

        $progressCalls = [];
        $engine->reindex(
            [
                new class() implements DynamicReindexProviderInterface {
                    public function total(string $index): int
                    {
                        if (TestingHelper::INDEX_SIMPLE !== $index) {
                            return 0;
                        }

                        return 1;
                    }

                    public function provide(string $index, ReindexConfig $reindexConfig): \Generator
                    {
                        if (TestingHelper::INDEX_SIMPLE !== $index) {
                            return;
                        }

                        yield [
                            'id' => '1',
                            'title' => 'Simple Document',
                        ];
                    }
                },
            ],
            ReindexConfig::create(),
            static function (string $index, int $count, int|null $total) use (&$progressCalls): void {
                $progressCalls[] = [$index, $count, $total];
            },
        );

        self::assertCount(2, $bulkCalls);
        self::assertSame($schema->indexes[TestingHelper::INDEX_COMPLEX]->name, $bulkCalls[0]['index']);
        self::assertSame([], $bulkCalls[0]['saveDocuments']);
        self::assertSame($schema->indexes[TestingHelper::INDEX_SIMPLE]->name, $bulkCalls[1]['index']);
        self::assertSame([
            [
                'id' => '1',
                'title' => 'Simple Document',
            ],
        ], $bulkCalls[1]['saveDocuments']);

        self::assertSame([
            [TestingHelper::INDEX_COMPLEX, 0, 0],
            [TestingHelper::INDEX_SIMPLE, 1, 1],
        ], $progressCalls);
    }
}
