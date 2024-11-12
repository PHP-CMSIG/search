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
        $task = $engine->createSchema(['return_slow_promise_result' => true]);
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

            $this->assertSame($expectedDocument, $loadedDocument);
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

    public static function setUpBeforeClass(): void
    {
        try {
            $task = self::getEngine()->dropSchema(['return_slow_promise_result' => true]);
            $task->wait();
        } catch (\Exception) {
            // ignore eventuell not existing indexes to drop
        }
    }

    public static function tearDownAfterClass(): void
    {
        $task = self::getEngine()->dropSchema(['return_slow_promise_result' => true]);
        $task->wait();
    }
}
