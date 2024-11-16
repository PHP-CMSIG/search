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

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use OpenSearch\Client;

final class OpensearchSchemaManager implements SchemaManagerInterface
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        return $this->client->indices()->exists([
            'index' => $index->name,
        ]);
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
                    'type' => 'keyword',
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line // TODO recheck doc_values https://github.com/php-cmsig/search/issues/65
                    'doc_values' => $field->filterable,
                ],
                $field instanceof Field\TextField => $properties[$name] = \array_replace([
                    'type' => 'text',
                    'index' => $field->searchable || $field->filterable, // TODO recheck doc_values https://github.com/php-cmsig/search/issues/65
                ], ($field->filterable || $field->sortable) ? [
                    'fields' => [
                        'raw' => [
                            'type' => 'keyword',
                            'index' => $field->searchable || $field->filterable, // TODO recheck doc_values https://github.com/php-cmsig/search/issues/65
                            'doc_values' => $field->filterable,
                        ],
                    ],
                ] : []),
                $field instanceof Field\BooleanField => $properties[$name] = [
                    'type' => 'boolean',
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line // TODO recheck doc_values https://github.com/php-cmsig/search/issues/65
                    'doc_values' => $field->filterable,
                ],
                $field instanceof Field\DateTimeField => $properties[$name] = [
                    'type' => 'date',
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line // TODO recheck doc_values https://github.com/php-cmsig/search/issues/65
                    'doc_values' => $field->filterable,
                ],
                $field instanceof Field\IntegerField => $properties[$name] = [
                    'type' => 'integer',
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line // TODO recheck doc_values https://github.com/php-cmsig/search/issues/65
                    'doc_values' => $field->filterable,
                ],
                $field instanceof Field\FloatField => $properties[$name] = [
                    'type' => 'float',
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line // TODO recheck doc_values https://github.com/php-cmsig/search/issues/65
                    'doc_values' => $field->filterable,
                ],
                $field instanceof Field\GeoPointField => $properties[$name] = [
                    'type' => 'geo_point',
                    'index' => $field->searchable,
                    'doc_values' => $field->filterable || $field->sortable,
                ],
                $field instanceof Field\ObjectField => $properties[$name] = [
                    'type' => 'object',
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
