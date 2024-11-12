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

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Field\GeoPointField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\AsyncTask;
use CmsIg\Seal\Task\TaskInterface;
use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;

final class MeilisearchSchemaManager implements SchemaManagerInterface
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        try {
            $this->client->getRawIndex($index->name);
        } catch (ApiException $e) {
            if (404 !== $e->httpStatus) {
                throw $e;
            }

            return false;
        }

        return true;
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        $deleteIndexResponse = $this->client->deleteIndex($index->name);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($deleteIndexResponse) {
            $this->client->waitForTask($deleteIndexResponse['taskUid']);
        });
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        $this->client->createIndex(
            $index->name,
            [
                'primaryKey' => $index->getIdentifierField()->name,
            ],
        );

        $attributes = [
            'searchableAttributes' => $index->searchableFields,
            'filterableAttributes' => $index->filterableFields,
            'sortableAttributes' => $index->sortableFields,
        ];

        $geoPointField = $index->getGeoPointField();
        if ($geoPointField instanceof GeoPointField) {
            foreach ($attributes as $listKey => $list) {
                foreach ($list as $key => $value) {
                    if ($value === $geoPointField->name) {
                        $attributes[$listKey][$key] = '_geo';
                    }
                }
            }
        }

        $updateIndexResponse = $this->client->index($index->name)
            ->updateSettings($attributes);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new AsyncTask(function () use ($updateIndexResponse) {
            $this->client->waitForTask($updateIndexResponse['taskUid']);
        });
    }
}
