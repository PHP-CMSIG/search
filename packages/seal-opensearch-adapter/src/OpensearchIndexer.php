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

namespace CmsIg\Seal\Adapter\Opensearch;

use CmsIg\Seal\Adapter\BulkHelper;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use OpenSearch\Client;

final class OpensearchIndexer implements IndexerInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new Marshaller(
            geoPointFieldConfig: [
                'latitude' => 'lat',
                'longitude' => 'lon',
            ],
        );
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        /** @var string|null $identifier */
        $identifier = $document[$identifierField->name] ?? null;

        $document = $this->marshaller->marshall($index->fields, $document);

        $data = $this->client->index([
            'index' => $index->name,
            'id' => (string) $identifier,
            'body' => $document,
            // TODO refresh should be refactored with async tasks
            'refresh' => $options['return_slow_promise_result'] ?? false, // update document immediately, so it is available in the `/_search` api directly
        ]);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        $document[$identifierField->name] = $data['_id'];

        return new SyncTask($document);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $data = $this->client->delete([
            'index' => $index->name,
            'id' => $identifier,
            // TODO refresh should be refactored with async tasks
            'refresh' => $options['return_slow_promise_result'] ?? false, // update document immediately, so it is no longer available in the `/_search` api directly
        ]);

        if ('deleted' !== $data['result']) {
            throw new \RuntimeException('Unexpected error while delete document with identifier "' . $identifier . '" from Index "' . $index->name . '".');
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        $batchIndexingResponses = [];
        foreach (BulkHelper::splitBulk($saveDocuments, $bulkSize) as $bulkSaveDocuments) {
            $params = ['body' => []];
            foreach ($bulkSaveDocuments as $document) {
                $document = $this->marshaller->marshall($index->fields, $document);

                /** @var string|int|null $identifier */
                $identifier = $document[$identifierField->name] ?? null;

                $params['body'][] = [
                    'index' => [
                        '_index' => $index->name,
                        '_id' => (string) $identifier,
                    ],
                ];

                $params['body'][] = $document;
            }

            $response = $this->client->bulk($params);

            if (false !== $response['errors']) {
                throw new \RuntimeException('Unexpected error while bulk indexing documents for index "' . $index->name . '".');
            }

            $batchIndexingResponses[] = $response;
        }

        foreach (BulkHelper::splitBulk($deleteDocumentIdentifiers, $bulkSize) as $bulkDeleteDocumentIdentifiers) {
            $params = ['body' => []];
            foreach ($bulkDeleteDocumentIdentifiers as $deleteDocumentIdentifier) {
                $params['body'][] = [
                    'delete' => [
                        '_index' => $index->name,
                        '_id' => $deleteDocumentIdentifier,
                    ],
                ];
            }

            $response = $this->client->bulk($params);

            if (false !== $response['errors']) {
                throw new \RuntimeException('Unexpected error while bulk deleting documents for index "' . $index->name . '".');
            }

            $batchIndexingResponses[] = $response;
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }
}
