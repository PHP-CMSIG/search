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

namespace CmsIg\Seal\Adapter\Typesense;

use CmsIg\Seal\Adapter\BulkHelper;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use Typesense\Client;

final class TypesenseIndexer implements IndexerInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new Marshaller(
            dateAsInteger: true,
            geoPointFieldConfig: [
                'latitude' => 0,
                'longitude' => 1,
            ],
        );
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        /** @var string|null $identifier */
        $identifier = ((string) $document[$identifierField->name]) ?? null; // @phpstan-ignore-line

        $marshalledDocument = $this->marshaller->marshall($index->fields, $document);
        $marshalledDocument['id'] = $identifier;

        $this->client->collections[$index->name]->documents->upsert($marshalledDocument);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask($document);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $this->client->collections[$index->name]->documents[$identifier]->delete();

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
            $marshalledBulkSaveDocuments = [];
            foreach ($bulkSaveDocuments as $document) {
                /** @var string|null $identifier */
                $identifier = ((string) $document[$identifierField->name]) ?? null; // @phpstan-ignore-line

                $marshalledDocument = $this->marshaller->marshall($index->fields, $document);
                $marshalledDocument['id'] = $identifier;

                $marshalledBulkSaveDocuments[] = $marshalledDocument;
            }

            $indexResponse = $this->client->collections[$index->name]->documents->import($marshalledBulkSaveDocuments);

            $batchIndexingResponses[] = $indexResponse;
        }

        foreach (BulkHelper::splitBulk($deleteDocumentIdentifiers, $bulkSize) as $bulkDeleteDocumentIdentifiers) {
            $filters = [];
            foreach ($bulkDeleteDocumentIdentifiers as $deleteDocumentIdentifier) {
                $filters[] = 'id:=' . $deleteDocumentIdentifier . '';
            }

            $deleteResponse = $this->client->collections[$index->name]->documents->delete([
                'filter_by' => \implode(' || ', $filters),
            ]);

            $batchIndexingResponses[] = $deleteResponse;
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }
}
