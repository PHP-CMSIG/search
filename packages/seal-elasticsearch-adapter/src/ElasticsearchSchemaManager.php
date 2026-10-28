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

namespace CmsIg\Seal\Adapter\Elasticsearch;

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;

final class ElasticsearchSchemaManager implements SchemaManagerInterface
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        /** @var Elasticsearch $response */
        $response = $this->client->indices()->exists([
            'index' => $index->name,
        ]);

        return 404 !== $response->getStatusCode();
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        $this->client->indices()->delete([
            'index' => $index->name,
        ]);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null); // TODO wait for index drop
    }

    public function updateIndex(Index $index, array $options = []): TaskInterface|null
    {
        /** @var Elasticsearch $response */
        $response = $this->client->indices()->getMapping([
            'index' => $index->name,
        ]);

        /** @var array<string, array{mappings?: array{properties?: array<string, mixed>}}> $mapping */
        $mapping = $response->asArray();

        $properties = $this->filterNewProperties(
            $this->createPropertiesMapping($index->fields),
            \array_values($mapping)[0]['mappings']['properties'] ?? [],
        );

        if ([] !== $properties) {
            $this->client->indices()->putMapping([
                'index' => $index->name,
                'body' => [
                    'properties' => $properties,
                ],
            ]);
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        $properties = $this->createPropertiesMapping($index->fields);

        $this->client->indices()->create([
            'index' => $index->name,
            'body' => [
                'mappings' => [
                    'dynamic' => 'strict',
                    'properties' => $properties,
                ],
            ],
        ]);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null); // TODO wait for index create
    }

    /**
     * Only properties missing in the existing mapping are returned, as the mapping of existing fields can not be changed.
     *
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $existingProperties
     *
     * @return array<string, mixed>
     */
    private function filterNewProperties(array $properties, array $existingProperties): array
    {
        $newProperties = [];

        foreach ($properties as $name => $property) {
            /** @var array<string, mixed> $property */
            if (!isset($existingProperties[$name])) {
                $newProperties[$name] = $property;

                continue;
            }

            if (isset($property['properties'])) {
                /** @var array<string, mixed> $existingProperty */
                $existingProperty = $existingProperties[$name];
                /** @var array<string, mixed> $nestedProperties */
                $nestedProperties = $property['properties'];
                /** @var array<string, mixed> $existingNestedProperties */
                $existingNestedProperties = $existingProperty['properties'] ?? [];

                $newNestedProperties = $this->filterNewProperties($nestedProperties, $existingNestedProperties);

                if ([] !== $newNestedProperties) {
                    $newProperties[$name] = ['type' => 'object', 'properties' => $newNestedProperties];
                }
            }
        }

        return $newProperties;
    }

    /**
     * @param array<string, Field\AbstractField> $fields
     *
     * @return array<string, mixed>
     */
    private function createPropertiesMapping(array $fields): array
    {
        /** @var array<string, mixed> $properties */
        $properties = [];

        foreach ($fields as $name => $field) {
            match (true) {
                $field instanceof Field\IdentifierField => $properties[$name] = [
                    'type' => 'keyword',
                    'index' => $field->searchable,
                    'doc_values' => $field->filterable || $field->sortable || $field->facet, // @phpstan-ignore-line
                ],
                $field instanceof Field\TextField => $properties[$name] = \array_replace([
                    'type' => 'text',
                    'index' => $field->searchable,
                ], ($field->filterable || $field->sortable || $field->distinct || $field->facet) ? [
                    'fields' => [
                        'raw' => ['type' => 'keyword'],
                    ],
                ] : []),
                $field instanceof Field\BooleanField => $properties[$name] = [
                    'type' => 'boolean',
                    'index' => $field->searchable,
                    'doc_values' => $field->filterable || $field->sortable || $field->facet,
                ],
                $field instanceof Field\DateTimeField => $properties[$name] = [
                    'type' => 'date',
                    'index' => $field->searchable,
                    'doc_values' => $field->filterable || $field->sortable || $field->facet,
                ],
                $field instanceof Field\IntegerField => $properties[$name] = [
                    'type' => 'integer',
                    'index' => $field->searchable,
                    'doc_values' => $field->filterable || $field->sortable || $field->facet,
                ],
                $field instanceof Field\FloatField => $properties[$name] = [
                    'type' => 'float',
                    'index' => $field->searchable,
                    'doc_values' => $field->filterable || $field->sortable || $field->facet,
                ],
                $field instanceof Field\GeoPointField => $properties[$name] = [
                    'type' => 'geo_point',
                    'index' => $field->searchable,
                    'doc_values' => $field->filterable || $field->sortable || $field->facet, // @phpstan-ignore-line
                ],
                $field instanceof Field\ObjectField => $properties[$name] = [
                    'type' => 'object',
                    'properties' => $this->createPropertiesMapping($field->fields),
                ],
                $field instanceof Field\JsonObjectField => $properties[$name] = [
                    'type' => 'keyword',
                    'index' => false,
                    'doc_values' => false,
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
