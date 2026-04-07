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
use CmsIg\Seal\Reindex\DynamicReindexProviderInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\IdentifierCondition;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Task\MultiTask;
use CmsIg\Seal\Task\TaskInterface;

final class Engine implements EngineInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly Schema $schema,
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

    public function countDocuments(string $index): int
    {
        return $this->adapter->getSearcher()->count($this->schema->indexes[$index]);
    }

    public function createSearchBuilder(string $index): SearchBuilder
    {
        return (new SearchBuilder(
            $this->schema,
            $this->adapter->getSearcher(),
        ))
            ->index($index);
    }

    public function createIndex(string $index, array $options = []): TaskInterface|null // @phpstan-ignore-line return.unusedType
    {
        return $this->adapter->getSchemaManager()->createIndex($this->schema->indexes[$index], $options);
    }

    public function dropIndex(string $index, array $options = []): TaskInterface|null // @phpstan-ignore-line return.unusedType
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

        return new MultiTask($tasks);
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

        return new MultiTask($tasks);
    }

    /**
     * TODO remove phpdoc when added to interface.
     *
     * @param array{return_slow_promise_result?: true} $options
     */
    public function reindex(
        iterable $reindexProviders,
        ReindexConfig $reindexConfig,
        callable|null $progressCallback = null,
        array $options = [],
    ): TaskInterface|null {
        /** @var array<string, array<DynamicReindexProviderInterface|ReindexProviderInterface>> $reindexProvidersPerIndex */
        $reindexProvidersPerIndex = [];
        /** @var array<string, string> $identifiersPerIndex */
        $identifiersPerIndex = [];
        foreach ($reindexProviders as $reindexProvider) {
            if ($reindexProvider instanceof DynamicReindexProviderInterface) {
                foreach ($this->schema->indexes as $indexName => $schemaIndex) {
                    if ($indexName !== $reindexConfig->getIndex() && null !== $reindexConfig->getIndex()) {
                        continue;
                    }

                    $identifiersPerIndex[$indexName] = $schemaIndex->getIdentifierField()->name;
                    $reindexProvidersPerIndex[$indexName][] = $reindexProvider;
                }

                continue;
            }

            if (!isset($this->schema->indexes[$reindexProvider::getIndex()])) {
                continue;
            }

            $identifiersPerIndex[$reindexProvider::getIndex()] = $this->schema->indexes[$reindexProvider::getIndex()]->getIdentifierField()->name;

            if ($reindexProvider::getIndex() === $reindexConfig->getIndex() || null === $reindexConfig->getIndex()) {
                $reindexProvidersPerIndex[$reindexProvider::getIndex()][] = $reindexProvider;
            }
        }

        // Track documents that need to be deleted if an identifiers array was given
        $documentIdsToDelete = \array_flip($reindexConfig->getIdentifiers());

        $tasks = [];
        foreach ($reindexProvidersPerIndex as $index => $reindexProviders) {
            if ($reindexConfig->shouldDropIndex() && $this->existIndex($index)) {
                $task = $this->dropIndex($index, ['return_slow_promise_result' => true]);
                $task->wait();
                $task = $this->createIndex($index, ['return_slow_promise_result' => true]);
                $task->wait();
            } elseif (!$this->existIndex($index)) {
                $task = $this->createIndex($index, ['return_slow_promise_result' => true]);
                $task->wait();
            }

            foreach ($reindexProviders as $reindexProvider) {
                $total = $reindexProvider instanceof DynamicReindexProviderInterface
                    ? $reindexProvider->total($index)
                    : $reindexProvider->total();

                if (0 === $total) {
                    continue;
                }

                $tasks[] = $this->bulk(
                    $index,
                    (static function () use ($index, $reindexProvider, $total, $reindexConfig, $progressCallback, &$documentIdsToDelete, $identifiersPerIndex) {
                        $count = 0;

                        $documents = $reindexProvider instanceof DynamicReindexProviderInterface
                            ? $reindexProvider->provide($index, $reindexConfig)
                            : $reindexProvider->provide($reindexConfig);

                        $lastCount = -1;
                        foreach ($documents as $document) {
                            ++$count;

                            // Document still exists, do not delete
                            unset($documentIdsToDelete[$document[$identifiersPerIndex[$index]]]); // @phpstan-ignore-line offsetAccess.invalidOffset

                            yield $document;

                            if (null !== $progressCallback
                                && 0 === ($count % $reindexConfig->getBulkSize())
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
                    $reindexConfig->getBulkSize(),
                    $options,
                );
            }
        }

        if ([] !== $documentIdsToDelete) {
            $index = $reindexConfig->getIndex();
            \assert(null !== $index, 'Index must be set if identifiers are given in reindex config.');
            $tasks[] = $this->bulk($index, [], \array_keys($documentIdsToDelete), $reindexConfig->getBulkSize(), $options);
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new MultiTask($tasks); // @phpstan-ignore-line
    }
}
