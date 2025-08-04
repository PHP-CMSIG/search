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

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use MongoDB\Response\MongoDB;

final class MongoDBSchemaManager implements SchemaManagerInterface
{
    public function __construct(
        private readonly ClientWrapper $client,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        $collectionNames = [...$this->client->getDatabase()->listCollectionNames()];

        return \in_array($index->name, $collectionNames, true);
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        $this->client->getDatabase()->dropCollection($index->name);
        $this->client->getDatabase()->getCollection($index->name)->dropSearchIndex($index->name . '-search-index');

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null); // TODO wait for index drop
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        // $searchDefinition = $this->createPropertiesMapping($index->fields);

        $this->client->getDatabase()->createCollection($index->name);  // TODO check with mongodb team how we could add stricter schema / validator

        $this->client->getDatabase()->getCollection($index->name)->createSearchIndex(
            ['mappings' => ['dynamic' => true]], // TODO check with mongodb team how to add a strict schema
            ['name' => $index->name . '-search-index'],
        );

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null); // TODO wait for index create
    }

    /**
     * @param Field\AbstractField[] $fields
     *
     * @return array<string, mixed>
     */
    private function createPropertiesMapping(array $fields): array
    {
        $properties = [];

        foreach ($fields as $name => $field) {
            match (true) {
                $field instanceof Field\IdentifierField => $properties[$name] = [
                    '$type' => 'string',
                    // TODO
                    // 'index' => $field->searchable,
                    // 'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\TextField => $properties[$name] = [
                    '$type' => 'string',
                    // TODO
                    // 'index' => $field->searchable,
                    // 'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\BooleanField => $properties[$name] = [
                    '$type' => 'boolean',
                    // TODO
                    // 'index' => $field->searchable,
                    // 'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\DateTimeField => $properties[$name] = [
                    '$type' => 'date',
                    // TODO
                    // 'index' => $field->searchable,
                    // 'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\IntegerField => $properties[$name] = [
                    '$type' => 'integer',
                    // TODO
                    // 'index' => $field->searchable,
                    // 'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\FloatField => $properties[$name] = [
                    '$type' => 'number',
                    // TODO
                    // 'index' => $field->searchable,
                    // 'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\GeoPointField => $properties[$name] = [
                    '$type' => 'Point',
                    // TODO
                    // 'index' => $field->searchable,
                    // 'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\ObjectField => $properties[$name] = [
                    '$type' => 'object',
                    'properties' => $this->createPropertiesMapping($field->fields),
                ],
                $field instanceof Field\TypedField => $properties = \array_replace($properties, $this->createTypedFieldMapping($name, $field)),
                default => throw new \RuntimeException(\sprintf('Field type "%s" is not supported.', $field::class)),
            };
        }

        return $properties;
    }

    /**
     * @return array<string, mixed>
     */
    private function createTypedFieldMapping(string $name, Field\TypedField $field): array
    {
        $typedProperties = [];

        foreach ($field->types as $type => $fields) {
            $typedProperties[$type] = [
                'type' => 'object',
                'properties' => $this->createPropertiesMapping($fields),
            ];

            if ($field->multiple) {
                $typedProperties[$type]['properties']['_originalIndex'] = [
                    'type' => 'integer',
                    'index' => false,
                ];
            }
        }

        return [$name => [
            'type' => 'object',
            'properties' => $typedProperties,
        ]];
    }
}
