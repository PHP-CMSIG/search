<?php

namespace CmsIg\Seal\Odm;

use CmsIg\Seal\Exception\DocumentNotFoundException;
use CmsIg\Seal\Odm\Reindex\OdmStaticReindexProviderInterface;
use CmsIg\Seal\Odm\Search\OdmSearchBuilder;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Task\TaskInterface;

interface OdmEngineInterface
{
    /**
     * @param $object $object
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<array<string, mixed>> : null)
     */
    public function saveDocument(string $index, object $object, array $options = []): TaskInterface|null;

    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function deleteDocument(string $index, string $identifier, array $options = []): TaskInterface|null;

    /**
     * @param iterable<object> $saveObjects
     * @param iterable<string> $deleteObjectIdentifiers
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function bulk(string $index, iterable $saveObjects, iterable $deleteObjectIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null;

    /**
     * @throws DocumentNotFoundException
     *
     * @return object
     */
    public function getDocument(string $index, string $identifier): object;

    public function countObjects(string $index): int;

    public function createSearchBuilder(string $index): OdmSearchBuilder;

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
     * @experimental This method is experimental and may change in future versions, we are not sure if it stays here or the syntax change completely.
     *               For framework users it is uninteresting as there it is handled via CLI commands.
     *
     * @param iterable<OdmStaticReindexProviderInterface> $odmReindexProviders
     * @param callable(string, int, int|null): void|null $progressCallback
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<null> : null)
     */
    public function reindex(// @phpstan-ignore-line parameter.notFound
        iterable $odmReindexProviders,
        ReindexConfig $reindexConfig,
        callable|null $progressCallback = null,
        array $options = [],
    );
}
