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

namespace CmsIg\Seal\Odm;

use CmsIg\Seal\Exception\DocumentNotFoundException;
use CmsIg\Seal\Odm\Reindex\OdmStaticReindexProviderInterface;
use CmsIg\Seal\Odm\Search\OdmSearchBuilder;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Task\TaskInterface;

interface OdmEngineInterface
{
    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<array<string, mixed>> : null)
     */
    public function saveDocument(object $object, array $options = []): TaskInterface|null;

    /**
     * @param class-string $documentClass
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function deleteDocument(string $documentClass, string $identifier, array $options = []): TaskInterface|null;

    /**
     * @template T of object
     *
     * @param class-string<T> $documentClass
     * @param iterable<T> $saveObjects
     * @param iterable<string> $deleteObjectIdentifiers
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function bulk(string $documentClass, iterable $saveObjects, iterable $deleteObjectIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null;

    /**
     * @template T of object
     *
     * @param class-string<T> $documentClass
     *
     * @throws DocumentNotFoundException
     *
     * @return T
     */
    public function getDocument(string $documentClass, string $identifier): object;

    /**
     * @param class-string $documentClass
     */
    public function countDocuments(string $documentClass): int;

    /**
     * @template T of object
     *
     * @param class-string<T> $documentClass
     *
     * @return OdmSearchBuilder<T>
     */
    public function createSearchBuilder(string $documentClass): OdmSearchBuilder;

    /**
     * @param class-string $documentClass
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function createIndex(string $documentClass, array $options = []): TaskInterface|null;

    /**
     * @param class-string $documentClass
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function dropIndex(string $documentClass, array $options = []): TaskInterface|null;

    /**
     * Updates an existing index to the current schema definition, keeping its documents. Only adding new fields is supported:
     * removed fields are not removed and changed fields (settings or type) are not guaranteed to be applied,
     * some adapters ignore them while others apply them. Such changes require a reindex of the documents.
     *
     * @param class-string $documentClass
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function updateIndex(string $documentClass, array $options = []): TaskInterface|null;

    /**
     * @param class-string $documentClass
     */
    public function existIndex(string $documentClass): bool;

    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<null> : null)
     */
    public function createSchema(array $options = []): TaskInterface|null;

    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<null> : null)
     */
    public function dropSchema(array $options = []): TaskInterface|null;

    /**
     * Updates all existing indexes to the current schema definition, keeping their documents. Only adding new fields
     * is supported, see {@see self::updateIndex()} for what is not guaranteed.
     *
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<null> : null)
     */
    public function updateSchema(array $options = []): TaskInterface|null;

    /**
     * @experimental This method is experimental and may change in future versions, we are not sure if it stays here or the syntax change completely.
     *               For framework users it is uninteresting as there it is handled via CLI commands.
     *
     * @param iterable<OdmStaticReindexProviderInterface> $odmReindexProviders
     * @param callable(string, int, int|null): void|null $progressCallback
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<null> : null)
     */
    public function reindex(
        iterable $odmReindexProviders,
        ReindexConfig $reindexConfig,
        callable|null $progressCallback = null,
        array $options = [],
    );
}
