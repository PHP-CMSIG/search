<?php

namespace Schranz\Search\SEAL\Testing;

use PHPUnit\Framework\TestCase;
use Schranz\Search\SEAL\Adapter\AdapterInterface;
use Schranz\Search\SEAL\Engine;
use Schranz\Search\SEAL\Exception\DocumentNotFoundException;
use Schranz\Search\SEAL\Schema\Schema;

abstract class AbstractAdapterTestCase extends TestCase
{
    protected static AdapterInterface $adapter;

    protected static Engine $engine;

    protected static Schema $schema;

    private static TaskHelper $taskHelper;

    public function setUp(): void
    {
        self::$taskHelper = new TaskHelper();
    }

    protected static function getEngine(): Engine
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

        foreach (array_keys($indexes) as $index) {
            $this->assertTrue($engine->existIndex($index));
        }

        $task = $engine->dropSchema(['return_slow_promise_result' => true]);
        $task->wait();

        foreach (array_keys($indexes) as $index) {
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

        $this->assertSame(
            count($documents),
            count($loadedDocuments),
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
            } catch (DocumentNotFoundException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue(
                $exceptionThrown,
                'Expected the exception "DocumentNotFoundException" to be thrown.'
            );
        }
    }

    public static function setUpBeforeClass(): void
    {
        $task = self::getEngine()->dropSchema(['return_slow_promise_result' => true]);
        $task->wait();
    }

    public static function tearDownAfterClass(): void
    {
        $task = self::getEngine()->dropSchema(['return_slow_promise_result' => true]);
        $task->wait();
    }
}
