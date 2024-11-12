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

namespace CmsIg\Seal\Adapter\RediSearch;

use CmsIg\Seal\Adapter\BulkHelper;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;

final class RediSearchIndexer implements IndexerInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly \Redis $client,
    ) {
        $this->marshaller = new Marshaller(
            addRawFilterTextField: true,
            geoPointFieldConfig: [
                'latitude' => 1,
                'longitude' => 0,
                'separator' => ',',
                'multiple' => true,
            ],
        );
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        /** @var string|int|null $identifier */
        $identifier = $document[$identifierField->name] ?? null;

        $marshalledDocument = $this->marshaller->marshall($index->fields, $document);

        $jsonSet = $this->client->rawCommand(
            'JSON.SET',
            $index->name . ':' . ((string) $identifier),
            '$',
            \json_encode($marshalledDocument, \JSON_THROW_ON_ERROR),
        );

        if (false === $jsonSet) {
            throw $this->createRedisLastErrorException();
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask($document);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $jsonDel = $this->client->rawCommand(
            'JSON.DEL',
            $index->name . ':' . $identifier,
        );

        if (false === $jsonDel) {
            throw $this->createRedisLastErrorException();
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    private function createRedisLastErrorException(): \RuntimeException
    {
        $lastError = $this->client->getLastError();
        $this->client->clearLastError();

        return new \RuntimeException('Redis: ' . $lastError);
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        foreach (BulkHelper::splitBulk($saveDocuments, $bulkSize) as $bulkSaveDocuments) {
            $multiClient = $this->client->multi();
            foreach ($bulkSaveDocuments as $document) {
                /** @var string|int|null $identifier */
                $identifier = $document[$identifierField->name] ?? null;

                $marshalledDocument = $this->marshaller->marshall($index->fields, $document);

                $multiClient->rawCommand(
                    'JSON.SET',
                    $index->name . ':' . ((string) $identifier),
                    '$',
                    \json_encode($marshalledDocument, \JSON_THROW_ON_ERROR),
                );
            }

            $multiExec = $multiClient->exec();

            if (false === $multiExec) {
                throw $this->createRedisLastErrorException();
            }
        }

        foreach (BulkHelper::splitBulk($deleteDocumentIdentifiers, $bulkSize) as $bulkDeleteDocumentIdentifiers) {
            $multiClient = $this->client->multi();
            foreach ($bulkDeleteDocumentIdentifiers as $deleteDocumentIdentifier) {
                $multiClient->rawCommand(
                    'JSON.DEL',
                    $index->name . ':' . $deleteDocumentIdentifier,
                );
            }

            $multiExec = $multiClient->exec();

            if (false === $multiExec) {
                throw $this->createRedisLastErrorException();
            }
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }
}
