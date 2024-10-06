<?php

declare(strict_types=1);

/*
 * This file is part of the Schranz Search package.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Schranz\Search\SEAL\Adapter\Loupe;

use Schranz\Search\SEAL\Adapter\BulkableIndexerInterface;
use Schranz\Search\SEAL\Adapter\IndexerInterface;
use Schranz\Search\SEAL\Marshaller\FlattenMarshaller;
use Schranz\Search\SEAL\Schema\Index;
use Schranz\Search\SEAL\Task\SyncTask;
use Schranz\Search\SEAL\Task\TaskInterface;

final class LoupeIndexer implements IndexerInterface, BulkableIndexerInterface
{
    private readonly FlattenMarshaller $marshaller;

    public function __construct(
        private readonly LoupeHelper $loupeHelper,
    ) {
        $this->marshaller = new FlattenMarshaller(
            dateAsInteger: true,
            separator: LoupeHelper::SEPARATOR,
            sourceField: LoupeHelper::SOURCE_FIELD,
            geoPointFieldConfig: [
                'latitude' => 'lat',
                'longitude' => 'lng',
            ],
        );
    }

    public function save(Index $index, array $document, array $options = []): TaskInterface|null
    {
        $loupe = $this->loupeHelper->getLoupe($index);

        $marshalledDocument = $this->marshaller->marshall($index->fields, $document);

        $loupe->addDocument($marshalledDocument);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask($document);
    }

    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null
    {
        $loupe = $this->loupeHelper->getLoupe($index);

        $loupe->deleteDocument($identifier);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function bulk(Index $index, iterable $saveDocuments, iterable $deleteDocumentIdentifiers, int $bulkSize = 100, array $options = []): TaskInterface|null
    {
        $loupe = $this->loupeHelper->getLoupe($index);

        $bulkedSaveDocuments = [];
        $count = 0;
        foreach ($saveDocuments as $document) {
            $bulkedSaveDocuments[] = $this->marshaller->marshall($index->fields, $document);
            ++$count;

            if (0 === ($count % $bulkSize)) {
                $loupe->addDocuments($bulkedSaveDocuments);
                $bulkedSaveDocuments = [];
            }
        }

        if ([] !== $bulkedSaveDocuments) {
            $loupe->addDocuments($bulkedSaveDocuments);
        }

        $count = 0;
        $bulkedDeleteDocumentIdentifiers = [];
        foreach ($deleteDocumentIdentifiers as $deleteDocumentIdentifier) {
            $bulkedDeleteDocumentIdentifiers[] = $deleteDocumentIdentifier;
            ++$count;

            if (0 === ($count % $bulkSize)) {
                $loupe->deleteDocuments($bulkedDeleteDocumentIdentifiers);
                $bulkedDeleteDocumentIdentifiers = [];
            }
        }

        if ([] !== $bulkedDeleteDocumentIdentifiers) {
            $loupe->deleteDocuments($bulkedDeleteDocumentIdentifiers);
        }

        return new SyncTask(null);
    }
}
