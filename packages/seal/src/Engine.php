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

namespace CmsIg\Seal;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Exception\DocumentNotFoundException;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\IdentifierCondition;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Task\MultiTask;
use CmsIg\Seal\Task\TaskInterface;

final class Engine implements EngineInterface
{
    public function __construct(
        readonly private AdapterInterface $adapter,
        readonly private Schema $schema,
    ) {
    }

    public function saveDocument(string $index, array $document, array $options = []): TaskInterface|null
    {
        return $this->adapter->getIndexer()->save(
            $this->schema->indexes[$index],
            $document,
            $options,
        );
    }

    public function deleteDocument(string $index, string $identifier, array $options = []): TaskInterface|null
    {
        return $this->adapter->getIndexer()->delete(
            $this->schema->indexes[$index],
            $identifier,
            $options,
        );
    }

    public function bulk(string $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        return $this->adapter->getIndexer()->bulk(
            $this->schema->indexes[$index],
            $saveDocuments,
            $deleteDocumentIdentifiers,
            $bulkSize,
            $options,
        );
    }

    public function getDocument(string $index, string $identifier): array
    {
        $documents = [...$this->createSearchBuilder($index)
            ->addFilter(new IdentifierCondition($identifier))
            ->limit(1)
            ->getResult()];

        /** @var array<string, mixed>|null $document */
        $document = $documents[0] ?? null;

        if (null === $document) {
            throw new DocumentNotFoundException(\sprintf(
                'Document with the identifier "%s" not found in index "%s".',
                $identifier,
                $index,
            ));
        }

        return $document;
    }

    public function createSearchBuilder(string $index): SearchBuilder
    {
        return (new SearchBuilder(
            $this->schema,
            $this->adapter->getSearcher(),
        ))
            ->index($index);
    }

    public function createIndex(string $index, array $options = []): TaskInterface|null
    {
        return $this->adapter->getSchemaManager()->createIndex($this->schema->indexes[$index], $options);
    }

    public function dropIndex(string $index, array $options = []): TaskInterface|null
    {
        return $this->adapter->getSchemaManager()->dropIndex($this->schema->indexes[$index], $options);
    }

    public function existIndex(string $index): bool
    {
        return $this->adapter->getSchemaManager()->existIndex($this->schema->indexes[$index]);
    }

    public function createSchema(array $options = []): TaskInterface|null
    {
        $tasks = [];
        foreach ($this->schema->indexes as $index) {
            $tasks[] = $this->adapter->getSchemaManager()->createIndex($index, $options);
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new MultiTask($tasks); // @phpstan-ignore-line
    }

    public function dropSchema(array $options = []): TaskInterface|null
    {
        $tasks = [];
        foreach ($this->schema->indexes as $index) {
            $tasks[] = $this->adapter->getSchemaManager()->dropIndex($index, $options);
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new MultiTask($tasks); // @phpstan-ignore-line
    }

    public function reindex(
        iterable $reindexProviders,
        string|null $index = null,
        bool $dropIndex = false,
        int $bulkSize = 100,
        callable|null $progressCallback = null,
    ): void {
        /** @var array<string, ReindexProviderInterface[]> $reindexProvidersPerIndex */
        $reindexProvidersPerIndex = [];
        foreach ($reindexProviders as $reindexProvider) {
            if (!isset($this->schema->indexes[$reindexProvider::getIndex()])) {
                continue;
            }

            if ($reindexProvider::getIndex() === $index || null === $index) {
                $reindexProvidersPerIndex[$reindexProvider::getIndex()][] = $reindexProvider;
            }
        }

        foreach ($reindexProvidersPerIndex as $index => $reindexProviders) {
            if ($dropIndex && $this->existIndex($index)) {
                $task = $this->dropIndex($index, ['return_slow_promise_result' => true]);
                $task->wait();
                $task = $this->createIndex($index, ['return_slow_promise_result' => true]);
                $task->wait();
            } elseif (!$this->existIndex($index)) {
                $task = $this->createIndex($index, ['return_slow_promise_result' => true]);
                $task->wait();
            }

            foreach ($reindexProviders as $reindexProvider) {
                $this->bulk(
                    $index,
                    (function () use ($index, $reindexProvider, $bulkSize, $progressCallback) {
                        $count = 0;
                        $total = $reindexProvider->total();

                        $lastCount = -1;
                        foreach ($reindexProvider->provide() as $document) {
                            ++$count;

                            yield $document;

                            if (null !== $progressCallback
                                && 0 === ($count % $bulkSize)
                            ) {
                                $lastCount = $count;
                                $progressCallback($index, $count, $total);
                            }
                        }

                        if ($lastCount !== $count
                            && null !== $progressCallback
                        ) {
                            $progressCallback($index, $count, $total);
                        }
                    })(),
                    [],
                    $bulkSize,
                );
            }
        }
    }
}
