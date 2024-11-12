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

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Schema;
use PHPUnit\Framework\TestCase;

abstract class AbstractSchemaManagerTestCase extends TestCase
{
    protected static SchemaManagerInterface $schemaManager;

    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = TestingHelper::createSchema();
    }

    public function testSimpleSchema(): void
    {
        $index = $this->schema->indexes[TestingHelper::INDEX_SIMPLE];

        $this->assertFalse(static::$schemaManager->existIndex($index));

        $task = static::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertTrue(static::$schemaManager->existIndex($index));

        $task = static::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertFalse(static::$schemaManager->existIndex($index));
    }

    public function testComplexSchema(): void
    {
        $index = $this->schema->indexes[TestingHelper::INDEX_COMPLEX];

        $this->assertFalse(static::$schemaManager->existIndex($index));

        $task = static::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertTrue(static::$schemaManager->existIndex($index));

        $task = static::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertFalse(static::$schemaManager->existIndex($index));
    }
}
