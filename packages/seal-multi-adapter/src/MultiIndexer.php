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

use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\AsyncTask;
use CmsIg\Seal\Task\MultiTask;
use CmsIg\Seal\Task\TaskInterface;

/**
 * @internal this class should never be needed to be instanced manually
 */
final class MultiIndexer implements IndexerInterface
{
    /**
     * @param iterable<IndexerInterface> $indexers
     */
    public function __construct(
        public readonly iterable $indexers,
    ) {
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $tasks = [];
        foreach ($this->indexers as $indexer) {
            $task = $indexer->save($index, $document, $options);

            if ($task instanceof TaskInterface) {
                $tasks[] = $task;
            }
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($tasks, $document) {
            $multiTask = new MultiTask($tasks);
            $multiTask->wait();

            return $document;
        });
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $tasks = [];
        foreach ($this->indexers as $indexer) {
            $task = $indexer->delete($index, $identifier, $options);

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

    public function bulk(
        Index $index,
        iterable $saveDocuments,
        iterable $deleteDocumentIdentifiers,
        int $bulkSize = 100,
        array $options = [],
    ): TaskInterface|null {
        $tasks = [];
        foreach ($this->indexers as $indexer) {
            $task = $indexer->bulk($index, $saveDocuments, $deleteDocumentIdentifiers, $bulkSize, $options);

            if ($task instanceof TaskInterface) {
                $tasks[] = $task;
            }
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new MultiTask($tasks);
    }
}
