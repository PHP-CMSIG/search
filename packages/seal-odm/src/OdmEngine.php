<?php

namespace CmsIg\Seal\Odm;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Odm\Mapper\OdmDataMapperInterface;
use CmsIg\Seal\Odm\Reindex\OdmDataMapperReindexProvider;
use CmsIg\Seal\Odm\Search\OdmSearchBuilder;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Task\TaskInterface;

final class OdmEngine implements OdmEngineInterface
{
    /**
     * @param EngineInterface $engine
     */
    public function __construct(
        protected readonly EngineInterface $engine,
        protected readonly OdmDataMapperInterface $dataMapper,
    ) {
    }

    public function saveDocument(string $index, object $object, array $options = []): TaskInterface|null
    {
        $document = $this->dataMapper->objectToArray($index, $object);

        return $this->engine->saveDocument($index, $document, $options);
    }

    public function deleteDocument(string $index, string $identifier, array $options = []): TaskInterface|null
    {
        return $this->engine->deleteDocument($index, $identifier, $options);
    }

    public function bulk(string $index, iterable $saveObjects, iterable $deleteObjectIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        return $this->engine->bulk($index, (function(string $index, iterable $objects) {
            foreach ($objects as $object) {
                yield $this->dataMapper->objectToArray($index, $object);
            }
        })($index, $saveObjects), $deleteObjectIdentifiers, $bulkSize, $options);
    }

    public function getDocument(string $index, string $identifier): object
    {
        $document = $this->engine->getDocument($index, $identifier);

        return $this->dataMapper->arrayToObject($index, $document);
    }

    public function countObjects(string $index): int
    {
        return $this->engine->countDocuments($index);
    }

    public function createSearchBuilder(string $index): OdmSearchBuilder
    {
        return new OdmSearchBuilder(
            $this->engine->createSearchBuilder($index),
            $this->dataMapper,
        );
    }

    public function createIndex(string $index, array $options = []): TaskInterface|null
    {
        return $this->engine->createIndex($index, $options);
    }

    public function dropIndex(string $index, array $options = []): TaskInterface|null
    {
        return $this->engine->dropIndex($index, $options);
    }

    public function existIndex(string $index): bool
    {
        return $this->engine->existIndex($index);
    }

    public function createSchema(array $options = []): TaskInterface|null
    {
        return $this->engine->createSchema($options);
    }

    public function dropSchema(array $options = []): TaskInterface|null
    {
        return $this->engine->dropSchema($options);
    }

    public function reindex(// @phpstan-ignore-line parameter.notFound
        iterable $odmReindexProviders,
        ReindexConfig $reindexConfig,
        callable|null $progressCallback = null,
        array $options = [],
    ): TaskInterface|null {
        $reindexProviders = (function(iterable $providers) {
            foreach ($providers as $provider) {
                yield new OdmDataMapperReindexProvider($provider, $this->dataMapper);
            }
        })($odmReindexProviders);

        return $this->engine->reindex($reindexProviders, $reindexConfig, $progressCallback, $options);
    }
}
