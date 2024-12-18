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

use CmsIg\Seal\Exception\DocumentNotFoundException;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Task\TaskInterface;

interface EngineInterface
{
    /**
     * @param array<string, mixed> $document
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<array<string, mixed>> : null)
     */
    public function saveDocument(string $index, array $document, array $options = []): TaskInterface|null;

    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function deleteDocument(string $index, string $identifier, array $options = []): TaskInterface|null;

    /**
     * @param iterable<array<string, mixed>> $saveDocuments
     * @param iterable<string> $deleteDocumentIdentifiers
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function bulk(string $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null;

    /**
     * @throws DocumentNotFoundException
     *
     * @return array<string, mixed>
     */
    public function getDocument(string $index, string $identifier): array;

    public function createSearchBuilder(string $index): SearchBuilder;

    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function createIndex(string $index, array $options = []): TaskInterface|null;

    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function dropIndex(string $index, array $options = []): TaskInterface|null;

    public function existIndex(string $index): bool;

    /**
     * @param array{return_slow_promise_result?: bool} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<null> : null)
     */
    public function createSchema(array $options = []): TaskInterface|null;

    /**
     * @param array{return_slow_promise_result?: bool} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<null> : null)
     */
    public function dropSchema(array $options = []): TaskInterface|null;

    /**
     * @experimental This method is experimental and may change in future versions, we are not sure if it stays here or the syntax change completely.
     *               For framework users it is uninteresting as there it is handled via CLI commands.
     *
     * @param iterable<ReindexProviderInterface> $reindexProviders
     * @param callable(string, int, int|null): void|null $progressCallback
     */
    public function reindex(
        iterable $reindexProviders,
        ReindexConfig $reindexConfig,
        callable|null $progressCallback = null,
    ): void;
}
