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

namespace CmsIg\Seal\Adapter\MongoDB;

use CmsIg\Seal\Adapter\BulkHelper;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;

final class MongoDBIndexer implements IndexerInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly ClientWrapper $client,
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

        /** @var string|int|null $identifier */
        $identifier = $document[$identifierField->name] ?? null;
        unset($document[$identifierField->name]);

        $document = $this->marshaller->marshall($index->fields, $document);
        $document['_id'] = $identifier;

        $this->client->getDatabase()
            ->getCollection($index->name)
            ->replaceOne(['_id' => $identifier], $document, ['upsert' => true]);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask($document);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $this->client->getDatabase()
            ->getCollection($index->name)
            ->deleteOne(['_id' => $identifier]);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        $identifierField = $index->getIdentifierField();

        foreach (BulkHelper::splitBulk($saveDocuments, $bulkSize) as $bulkSaveDocuments) {
            $documents = [];
            foreach ($bulkSaveDocuments as $document) {
                $document = $this->marshaller->marshall($index->fields, $document);

                /** @var string|int|null $identifier */
                $identifier = $document[$identifierField->name] ?? null;
                unset($document[$identifierField->name]);
                $document['_id'] = $identifier;

                $documents[] = $document;
            }

            $this->client->getDatabase()
                ->getCollection($index->name)
                ->insertMany($documents);
        }

        foreach (BulkHelper::splitBulk($deleteDocumentIdentifiers, $bulkSize) as $bulkDeleteDocumentIdentifiers) {
            $this->client->getDatabase()
                ->getCollection($index->name)
                ->deleteMany([
                    '_id' => ['$in' => $bulkDeleteDocumentIdentifiers],
                ]);
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }
}
