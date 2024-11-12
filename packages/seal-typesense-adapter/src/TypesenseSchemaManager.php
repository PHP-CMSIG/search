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

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

final class TypesenseSchemaManager implements SchemaManagerInterface
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        try {
            $this->client->collections[$index->name]->retrieve();
        } catch (ObjectNotFound) {
            return false;
        }

        return true;
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        $this->client->collections[$index->name]->delete();

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        $fields = $this->createFields($index->fields);

        $this->client->collections->create([
            'name' => $index->name,
            'enable_nested_fields' => true, // see https://github.com/typesense/typesense/issues/227#issuecomment-1211666924
            'fields' => $fields,
        ]);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    /**
     * @param Field\AbstractField[] $indexFields
     *
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     optional?: bool,
     *     index?: bool,
     *     facet?: bool,
     * }>
     */
    private function createFields(array $indexFields): array
    {
        /**
         * @var array<int, array{
         *     name: string,
         *     type: string,
         *     optional?: bool,
         *     index?: bool,
         *     facet?: bool,
         * }> $fields
         */
        $fields = [];

        foreach ($indexFields as $name => $field) {
            match (true) {
                $field instanceof Field\IdentifierField => $fields[] = [
                    'name' => $name,
                    'type' => 'string',
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line
                    'facet' => $field->filterable,
                ],
                $field instanceof Field\TextField => $fields[] = [
                    'name' => $name,
                    'type' => $field->multiple ? 'string[]' : 'string',
                    'optional' => true,
                    'index' => $field->searchable || $field->filterable,
                    'facet' => $field->filterable,
                ],
                $field instanceof Field\BooleanField => $fields[] = [
                    'name' => $name,
                    'type' => $field->multiple ? 'bool[]' : 'bool',
                    'optional' => true,
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line
                    'facet' => $field->filterable,
                ],
                $field instanceof Field\IntegerField => $fields[] = [
                    'name' => $name,
                    'type' => $field->multiple ? 'int64[]' : 'int64',
                    'optional' => true,
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line
                    'facet' => $field->filterable,
                ],
                $field instanceof Field\DateTimeField => $fields[] = [
                    'name' => $name,
                    'type' => $field->multiple ? 'int64[]' : 'int64',
                    'optional' => true,
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line
                    'facet' => $field->filterable,
                ],
                $field instanceof Field\FloatField => $fields[] = [
                    'name' => $name,
                    'type' => $field->multiple ? 'float[]' : 'float',
                    'optional' => true,
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line
                    'facet' => $field->filterable,
                ],
                $field instanceof Field\GeoPointField => $fields[] = [
                    'name' => $name,
                    'type' => $field->multiple ? 'geopoint[]' : 'geopoint',
                    'optional' => true,
                    'index' => $field->searchable || $field->filterable, // @phpstan-ignore-line
                    'facet' => $field->filterable,
                ],
                $field instanceof Field\ObjectField => $fields = [...$fields, ...$this->createObjectFields($name, $field)],
                $field instanceof Field\TypedField => $fields = [...$fields, ...$this->createTypedFields($name, $field)],
                default => throw new \RuntimeException(\sprintf('Field type "%s" is not supported.', $field::class)),
            };
        }

        return $fields;
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     optional?: bool,
     *     index?: bool,
     *     facet?: bool,
     * }>
     */
    private function createObjectFields(string $name, Field\ObjectField $field): array
    {
        $fields = [
            [
                'name' => $name,
                'type' => $field->multiple ? 'object[]' : 'object',
                'optional' => true,
            ],
        ];

        $nestedFields = $this->createFields($field->fields);
        foreach ($nestedFields as $nestedField) {
            $nestedField['name'] = $name . '.' . $nestedField['name'];
            $nestedField['type'] = $field->multiple
                ? (\str_contains($nestedField['type'], '[]') ? $nestedField['type'] : $nestedField['type'] . '[]')
                : $nestedField['type'];
            $fields[] = $nestedField;
        }

        return $fields;
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     optional?: bool,
     *     index?: bool,
     *     facet?: bool,
     * }>
     */
    private function createTypedFields(string $name, Field\TypedField $field): array
    {
        $fields = [
            [
                'name' => $name,
                'type' => 'object',
                'optional' => true,
            ],
        ];

        foreach ($field->types as $type => $typeFields) {
            $fields[] = [
                'name' => $name . '.' . $type,
                'type' => $field->multiple ? 'object[]' : 'object',
                'optional' => true,
            ];

            if ($field->multiple) {
                $fields[] = [
                    'name' => $name . '.' . $type . '._originalIndex',
                    'type' => 'int64[]',
                    'optional' => true,
                ];
            }

            $nestedFields = $this->createFields($typeFields);
            foreach ($nestedFields as $nestedField) {
                $nestedField['name'] = $name . '.' . $type . '.' . $nestedField['name'];
                $nestedField['type'] = $field->multiple
                    ? (\str_contains($nestedField['type'], '[]') ? $nestedField['type'] : $nestedField['type'] . '[]')
                    : $nestedField['type'];
                $fields[] = $nestedField;
            }
        }

        return $fields;
    }
}
