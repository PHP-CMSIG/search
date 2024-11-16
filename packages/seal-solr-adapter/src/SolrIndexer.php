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

namespace CmsIg\Seal\Adapter\Solr;

use CmsIg\Seal\Adapter\BulkHelper;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\FlattenMarshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use Solarium\Client;

final class SolrIndexer implements IndexerInterface
{
    private readonly FlattenMarshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new FlattenMarshaller(
            addRawFilterTextField: true,
            geoPointFieldConfig: [
                'latitude' => 0,
                'longitude' => 1,
                'separator' => ',',
                'multiple' => false,
            ],
        );
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        /** @var string|int|null $identifier */
        $identifier = $document[$identifierField->name] ?? null;

        $marshalledDocument = $this->marshaller->marshall($index->fields, $document);
        $marshalledDocument['id'] = (string) $identifier; // Solr currently does not support set another identifier then id: https://github.com/php-cmsig/search/issues/87

        $update = $this->client->createUpdate();
        $indexDocument = $update->createDocument($marshalledDocument);

        $update->addDocuments([$indexDocument]);
        $update->addCommit();

        $this->client->getEndpoint()
            ->setCollection($index->name);

        $this->client->update($update);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask($document);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $update = $this->client->createUpdate();
        $update->addDeleteById($identifier);
        $update->addCommit();

        $this->client->getEndpoint()
            ->setCollection($index->name);

        $this->client->update($update);

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
            $update = $this->client->createUpdate();
            foreach ($bulkSaveDocuments as $document) {
                /** @var string|int|null $identifier */
                $identifier = $document[$identifierField->name] ?? null;

                $marshalledDocument = $this->marshaller->marshall($index->fields, $document);
                $marshalledDocument['id'] = (string) $identifier; // Solr currently does not support set another identifier then id: https://github.com/php-cmsig/search/issues/87

                $marshalledBulkSaveDocuments[] = $update->createDocument($marshalledDocument);
            }

            $update->addDocuments($marshalledBulkSaveDocuments);
            $update->addCommit();

            $this->client->getEndpoint()
                ->setCollection($index->name);

            $this->client->update($update);
        }

        foreach (BulkHelper::splitBulk($deleteDocumentIdentifiers, $bulkSize) as $bulkDeleteDocumentIdentifiers) {
            $update = $this->client->createUpdate();
            foreach ($bulkDeleteDocumentIdentifiers as $deleteDocumentIdentifier) {
                $update->addDeleteById($deleteDocumentIdentifier);
            }
            $update->addCommit();

            $this->client->getEndpoint()
                ->setCollection($index->name);

            $this->client->update($update);
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }
}
