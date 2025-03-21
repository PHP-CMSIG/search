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
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\SearchBuilder;
use PHPUnit\Framework\TestCase;

abstract class AbstractStatisticsTestCase extends TestCase
{
    protected static AdapterInterface $adapter;

    protected static SchemaManagerInterface $schemaManager;

    protected static IndexerInterface $indexer;

    protected static SearcherInterface $searcher;

    protected static Schema $schema;

    private static TaskHelper $taskHelper;

    protected function setUp(): void
    {
        self::$taskHelper = new TaskHelper();
    }

    public static function setUpBeforeClass(): void
    {
        self::$schemaManager = self::$adapter->getSchemaManager();
        self::$indexer = self::$adapter->getIndexer();
        self::$searcher = self::$adapter->getSearcher();

        self::$taskHelper = new TaskHelper();
        foreach (self::getSchema()->indexes as $index) {
            if (self::$schemaManager->existIndex($index)) {
                self::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true])->wait();
            }

            self::$taskHelper->tasks[] = self::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();
    }

    public static function tearDownAfterClass(): void
    {
        self::$taskHelper->waitForAll();

        foreach (self::getSchema()->indexes as $index) {
            self::$taskHelper->tasks[] = self::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();
    }

    protected static function getSchema(): Schema
    {
        if (!isset(self::$schema)) {
            self::$schema = TestingHelper::createSchema();
        }

        return self::$schema;
    }

    public function testDocumentCount(): void
    {
        $schema = self::getSchema();
        $this->assertSame(
            0,
            self::$adapter->getStatistics()->getStatistics($schema->indexes[TestingHelper::INDEX_COMPLEX])->getNumberOfDocuments()
        );

        $documents = TestingHelper::createComplexFixtures();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $this->assertSame(
            \count($documents),
            self::$adapter->getStatistics()->getStatistics($schema->indexes[TestingHelper::INDEX_COMPLEX])->getNumberOfDocuments()
        );
    }
}
