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

namespace CmsIg\Seal\Adapter\RediSearch;

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;

final class RediSearchSchemaManager implements SchemaManagerInterface
{
    public function __construct(
        private readonly \Redis $client,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        try {
            $indexInfo = $this->client->rawCommand('FT.INFO', $index->name);
        } catch (\RedisException $e) {
            if ('unknown index name' !== \strtolower($e->getMessage())) {
                throw $e;
            }

            return false;
        }

        return true;
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        $dropIndex = $this->client->rawCommand('FT.DROPINDEX', $index->name);
        if (false === $dropIndex) {
            throw $this->createRedisLastErrorException();
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        $indexFields = $this->createJsonFields($index->fields);

        $properties = [];
        foreach ($indexFields as $name => $indexField) {
            $properties[] = $indexField['jsonPath'];
            $properties[] = 'AS';
            $properties[] = \str_replace('.', '__', $name);
            $properties[] = $indexField['type'];

            if (!$indexField['searchable'] && !$indexField['filterable']) { // TODO check if we can make something filterable but not searchable
                $properties[] = 'NOINDEX';
            }

            if ($indexField['sortable']) {
                $properties[] = 'SORTABLE';
            }
        }

        $createIndex = $this->client->rawCommand(
            'FT.CREATE',
            $index->name,
            'ON',
            'JSON',
            'PREFIX',
            '1',
            $index->name,
            'SCHEMA',
            ...$properties,
        );

        if (false === $createIndex) {
            throw $this->createRedisLastErrorException();
        }

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    /**
     * @param Field\AbstractField[] $fields
     *
     * @return array<string, array{
     *     jsonPath: string,
     *     type: string,
     *     searchable: bool,
     *     sortable: bool,
     *     filterable: bool,
     * }>
     */
    private function createJsonFields(array $fields, string $prefix = '', string $jsonPathPrefix = '$'): array
    {
        $indexFields = [];

        foreach ($fields as $name => $field) {
            $jsonPath = $jsonPathPrefix . '[\'' . $name . '\']';
            $jsonPathRaw = $jsonPathPrefix . '[\'' . $name . '.raw\']';
            if ($field->multiple) {
                $jsonPath .= '[*]';
                $jsonPathRaw .= '[*]';
            }
            $name = $prefix . $name;

            // ignore all fields without search, sort or filterable activated
            if (!$field->searchable && !$field->sortable && !$field->filterable) {
                continue;
            }

            match (true) {
                $field instanceof Field\IdentifierField => $indexFields[$name] = [
                    'jsonPath' => $jsonPath,
                    'type' => 'TAG',
                    'searchable' => $field->searchable,
                    'sortable' => $field->sortable,
                    'filterable' => $field->filterable,
                ],
                $field instanceof Field\TextField, $field instanceof Field\DateTimeField => $indexFields = \array_replace($indexFields, $field->searchable ? [
                    $name => [
                        'jsonPath' => $jsonPath,
                        'type' => 'TEXT',
                        'searchable' => $field->searchable,
                        'sortable' => false,
                        'filterable' => false,
                    ],
                ] : [], $field->filterable || $field->sortable ? [
                    $name . '.raw' => [
                        'jsonPath' => $jsonPathRaw,
                        'type' => 'TAG',
                        'searchable' => false,
                        'sortable' => $field->sortable,
                        'filterable' => $field->filterable,
                    ],
                ] : []),
                $field instanceof Field\BooleanField => $indexFields[$name] = [
                    'jsonPath' => $jsonPath,
                    'type' => 'TAG',
                    'searchable' => $field->searchable,
                    'sortable' => $field->sortable,
                    'filterable' => $field->filterable,
                ],
                $field instanceof Field\IntegerField, $field instanceof Field\FloatField => $indexFields[$name] = [
                    'jsonPath' => $jsonPath,
                    'type' => 'NUMERIC',
                    'searchable' => $field->searchable,
                    'sortable' => $field->sortable,
                    'filterable' => $field->filterable,
                ],
                $field instanceof Field\GeoPointField => $indexFields[$name] = [
                    'jsonPath' => $jsonPath,
                    'type' => 'GEO',
                    'searchable' => $field->searchable,
                    'sortable' => $field->sortable,
                    'filterable' => $field->filterable,
                ],
                $field instanceof Field\ObjectField => $indexFields = \array_replace($indexFields, $this->createJsonFields($field->fields, $name, $jsonPath)),
                $field instanceof Field\TypedField => \array_map(function ($fields, $type) use ($name, &$indexFields, $jsonPath, $field) {
                    $newJsonPath = $jsonPath . '[\'' . $type . '\']';
                    if ($field->multiple) {
                        $newJsonPath = \substr($jsonPath, 0, -3) . '[\'' . $type . '\'][*]';
                    }

                    $indexFields = \array_replace($indexFields, $this->createJsonFields($fields, $name . '.' . $type . '.', $newJsonPath));
                }, $field->types, \array_keys($field->types)),
                default => throw new \RuntimeException(\sprintf('Field type "%s" is not supported.', $field::class)),
            };
        }

        return $indexFields;
    }

    private function createRedisLastErrorException(): \RedisException
    {
        $lastError = $this->client->getLastError();
        $this->client->clearLastError();

        return new \RedisException('Redis: ' . $lastError);
    }
}
