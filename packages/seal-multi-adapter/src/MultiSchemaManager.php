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

namespace CmsIg\Seal\Adapter\Multi;

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\AsyncTask;
use CmsIg\Seal\Task\MultiTask;
use CmsIg\Seal\Task\TaskInterface;

/**
 * @internal this class should never be needed to be instanced manually
 */
final class MultiSchemaManager implements SchemaManagerInterface
{
    /**
     * @param iterable<SchemaManagerInterface> $schemaManagers
     */
    public function __construct(
        public readonly iterable $schemaManagers,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        $existIndex = true;
        foreach ($this->schemaManagers as $schemaManager) {
            $existIndex = $existIndex && $schemaManager->existIndex($index);
        }

        return $existIndex;
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        $tasks = [];

        foreach ($this->schemaManagers as $schemaManager) {
            $task = $schemaManager->dropIndex($index, $options);

            if ($task instanceof TaskInterface) {
                $tasks[] = $task;
            }
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($tasks): void {
            $multiTask = new MultiTask($tasks);
            $multiTask->wait();
        });
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        $tasks = [];
        foreach ($this->schemaManagers as $schemaManager) {
            $task = $schemaManager->createIndex($index, $options);

            if ($task instanceof TaskInterface) {
                $tasks[] = $task;
            }
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($tasks): void {
            $multiTask = new MultiTask($tasks);
            $multiTask->wait();
        });
    }
}
