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

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Odm\Mapper\OdmDataMapperInterface;
use CmsIg\Seal\Odm\Reindex\OdmDataMapperReindexProvider;
use CmsIg\Seal\Odm\Search\OdmSearchBuilder;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Task\TaskInterface;

final class OdmEngine implements OdmEngineInterface
{
    public function __construct(
        private readonly EngineInterface $engine,
        private readonly OdmDataMapperInterface $dataMapper,
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
        return $this->engine->bulk($index, (function (string $index, iterable $objects) {
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

    public function reindex(
        iterable $odmReindexProviders,
        ReindexConfig $reindexConfig,
        callable|null $progressCallback = null,
        array $options = [],
    ): TaskInterface|null {
        return $this->engine->reindex(
            [new OdmDataMapperReindexProvider($odmReindexProviders, $this->dataMapper)],
            $reindexConfig,
            $progressCallback,
            $options,
        );
    }
}
