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

namespace CmsIg\Seal\Adapter\Meilisearch;

use CmsIg\Seal\Adapter\BulkHelper;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\AsyncTask;
use CmsIg\Seal\Task\TaskInterface;
use Meilisearch\Client;

final class MeilisearchIndexer implements IndexerInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new Marshaller(
            geoPointFieldConfig: [
                'name' => '_geo',
                'latitude' => 'lat',
                'longitude' => 'lng',
            ],
        );
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        /** @var string|int|null $identifier */
        $identifier = $document[$identifierField->name] ?? null;

        $indexResponse = $this->client->index($index->name)->addDocuments([
            $this->marshaller->marshall($index->fields, $document),
        ], $identifierField->name);

        if ('enqueued' !== $indexResponse['status']) {
            throw new \RuntimeException('Unexpected error while save document with identifier "' . $identifier . '" into Index "' . $index->name . '".');
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($indexResponse, $document) {
            $this->client->waitForTask($indexResponse['taskUid']);

            return $document;
        });
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $deleteResponse = $this->client->index($index->name)->deleteDocument($identifier);

        if ('enqueued' !== $deleteResponse['status']) {
            throw new \RuntimeException('Unexpected error while delete document with identifier "' . $identifier . '" from Index "' . $index->name . '".');
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($deleteResponse) {
            $this->client->waitForTask($deleteResponse['taskUid']);
        });
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        $batchIndexingResponses = [];
        foreach (BulkHelper::splitBulk($saveDocuments, $bulkSize) as $bulkSaveDocuments) {
            $marshalledBulkSaveDocuments = [];
            foreach ($bulkSaveDocuments as $document) {
                $document = $this->marshaller->marshall($index->fields, $document);
                $marshalledBulkSaveDocuments[] = $document;
            }

            $indexResponse = $this->client->index($index->name)->addDocuments(
                $marshalledBulkSaveDocuments,
                $identifierField->name,
            );

            if ('enqueued' !== $indexResponse['status']) {
                throw new \RuntimeException('Unexpected error while save documents into Index "' . $index->name . '".');
            }

            $batchIndexingResponses[] = $indexResponse;
        }

        foreach (BulkHelper::splitBulk($deleteDocumentIdentifiers, $bulkSize) as $bulkDeleteDocumentIdentifiers) {
            $filters = [];
            foreach ($bulkDeleteDocumentIdentifiers as $deleteDocumentIdentifier) {
                $filters[] = '(' . $identifierField->name . ' = ' . $deleteDocumentIdentifier . ')';
            }

            $deleteResponse = $this->client->index($index->name)->deleteDocuments([
                'filter' => \implode(' OR ', $filters),
            ]);

            if ('enqueued' !== $deleteResponse['status']) {
                throw new \RuntimeException('Unexpected error while delete documents from Index "' . $index->name . '".');
            }

            $batchIndexingResponses[] = $deleteResponse;
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($batchIndexingResponses) {
            foreach ($batchIndexingResponses as $batchIndexingResponse) {
                $this->client->waitForTask($batchIndexingResponse['taskUid']);
            }
        });
    }
}
