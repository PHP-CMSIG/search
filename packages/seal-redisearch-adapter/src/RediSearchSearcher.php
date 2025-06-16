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

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;

final class RediSearchSearcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly \Redis $client,
    ) {
        $this->marshaller = new Marshaller(
            addRawFilterTextField: true,
            geoPointFieldConfig: [
                'latitude' => 1,
                'longitude' => 0,
                'separator' => ',',
                'multiple' => true,
            ],
        );
    }

    public function search(Search $search): Result
    {
        if (
            1 === \count($search->filters)
            && $search->filters[0] instanceof Condition\IdentifierCondition
            && 0 === $search->offset
            && 1 === $search->limit
        ) {
            $key = $search->index->name . ':' . $search->filters[0]->identifier;

            return $this->searchByIdentifier($search, $key);
        }

        $parameters = [];
        $query = $this->recursiveResolveFilterConditions($search->index, $search->filters, true, $parameters) ?: '*';

        if (null !== $search->distinct) {
            return $this->searchGrouped($search, $query);
        }

        return $this->searchDirectly($search, $query, $parameters);
    }

    private function searchGrouped(Search $search, string $query): Result
    {
        $distinctField = '@' . $search->distinct;
        $identifierField = '@' . $search->index->getIdentifierField()->name;

        $arguments = [
            'GROUPBY', 1, $distinctField,
            'REDUCE', 'FIRST_VALUE', '1', $identifierField, 'AS', 'documentId',
            'DIALECT', '3',
        ];

        /** @var array<mixed>|false $result */
        $result = $this->client->rawCommand('FT.AGGREGATE', $search->index->name, $query, ...$arguments);

        if (false === $result) {
            throw $this->createRedisLastErrorException();
        }

        $documentIds = [];
        /** @var int $total */
        $total = $result[0];

        for ($i = 1; $i <= $total; ++$i) {
            $row = [];
            foreach ((array) $result[$i] as $j => $value) {
                if (0 === $j % 2 && isset($result[$i][$j + 1])) {
                    $row[$value] = $result[$i][$j + 1];
                }
            }
            if (isset($row['documentId'])) {
                $documentIds[] = $row['documentId'];
            }
        }

        if ([] === $documentIds) {
            return new Result($this->hitsToDocuments($search->index, []), 0);
        }

        $identifierFieldName = $search->index->getIdentifierField()->name;
        $escapedIds = \array_map([$this, 'escapeFilterValue'], $documentIds);
        $searchQuery = \sprintf('@%s:{%s}', $identifierFieldName, \implode('|', $escapedIds));

        $parameters = [];

        return $this->searchDirectly($search, $searchQuery, $parameters);
    }

    private function searchByIdentifier(Search $search, string $key): Result
    {
        /** @var string|false $jsonGet */
        $jsonGet = $this->client->rawCommand('JSON.GET', $key);

        if (false === $jsonGet) {
            return new Result($this->hitsToDocuments($search->index, []), 0);
        }

        /** @var array<string, mixed> $document */
        $document = \json_decode($jsonGet, true, flags: \JSON_THROW_ON_ERROR);

        return new Result($this->hitsToDocuments($search->index, [$document]), 1);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function searchDirectly(Search $search, string $query, array $parameters): Result
    {
        $arguments = [];

        foreach ($search->sortBys as $field => $direction) {
            $arguments[] = 'SORTBY';
            $arguments[] = $this->escapeFilterValue($field);
            $arguments[] = \strtoupper((string) $this->escapeFilterValue($direction));
        }

        if ($search->offset || $search->limit) {
            $arguments[] = 'LIMIT';
            $arguments[] = $search->offset;
            $arguments[] = ($search->limit ?: 10);
        }

        if ([] !== $parameters) {
            $arguments[] = 'PARAMS';
            $arguments[] = \count($parameters) * 2;
            foreach ($parameters as $key => $value) {
                $arguments[] = $key;
                $arguments[] = $value;
            }
        }

        $arguments[] = 'DIALECT';
        $arguments[] = '2';

        /** @var mixed[]|false $result */
        $result = $this->client->rawCommand('FT.SEARCH', $search->index->name, $query, ...$arguments);
        if (false === $result) {
            throw $this->createRedisLastErrorException();
        }

        /** @var int $total */
        $total = $result[0];
        $documents = [];

        foreach ($result as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $previousValue = null;
            foreach ($item as $value) {
                if ('$' === $previousValue) {
                    /** @var array<string, mixed> $document */
                    $document = \json_decode($value, true, flags: \JSON_THROW_ON_ERROR);
                    $documents[] = $document;
                }
                $previousValue = $value;
            }
        }

        return new Result(
            $this->hitsToDocuments($search->index, $documents),
            $total,
        );
    }

    /**
     * @param iterable<array<string, mixed>> $hits
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(Index $index, iterable $hits): \Generator
    {
        foreach ($hits as $hit) {
            yield $this->marshaller->unmarshall($index->fields, $hit);
        }
    }

    private function getFilterField(Index $index, string $name): string
    {
        $field = $index->getFieldByPath($name);

        if ($field instanceof Field\TextField) {
            $name .= '__raw';
        }

        return \str_replace('.', '__', $name);
    }

    private function createRedisLastErrorException(): \RuntimeException
    {
        $lastError = $this->client->getLastError();
        $this->client->clearLastError();

        return new \RuntimeException('Redis: ' . $lastError);
    }

    private function escapeFilterValue(string|int|float|bool $value): string
    {
        return match (true) {
            \is_string($value) => \str_replace(
                ["\n", "\r", "\t"],
                ["\\\n", "\\\r", "\\\t"], // double escaping required see https://github.com/RediSearch/RediSearch/issues/4092#issuecomment-1819932938
                \addcslashes($value, ',./(){}[]:;~!@#$%^&*-=+|\'`"<>? '),
            ),
            \is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    /**
     * @param object[] $conditions
     * @param array<string, string> $parameters
     */
    private function recursiveResolveFilterConditions(Index $index, array $conditions, bool $conjunctive, array &$parameters): string
    {
        $filters = [];

        foreach ($conditions as $filter) {
            $filter = match (true) {
                $filter instanceof Condition\InCondition => $filter->createOrCondition(),
                $filter instanceof Condition\NotInCondition => $filter->createAndCondition(),
                default => $filter,
            };

            match (true) {
                $filter instanceof Condition\SearchCondition => $filters[] = '%%' . \implode('%% ', \explode(' ', $this->escapeFilterValue($filter->query))) . '%%', // levenshtein of 2 per word
                $filter instanceof Condition\IdentifierCondition => $filters[] = '@' . $index->getIdentifierField()->name . ':{' . $this->escapeFilterValue($filter->identifier) . '}',
                $filter instanceof Condition\EqualCondition => $filters[] = '@' . $this->getFilterField($index, $filter->field) . ':{' . $this->escapeFilterValue($filter->value) . '}',
                $filter instanceof Condition\NotEqualCondition => $filters[] = '-@' . $this->getFilterField($index, $filter->field) . ':{' . $this->escapeFilterValue($filter->value) . '}',
                $filter instanceof Condition\GreaterThanCondition => $filters[] = '@' . $this->getFilterField($index, $filter->field) . ':[(' . $this->escapeFilterValue($filter->value) . ' inf]',
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = '@' . $this->getFilterField($index, $filter->field) . ':[' . $this->escapeFilterValue($filter->value) . ' inf]',
                $filter instanceof Condition\LessThanCondition => $filters[] = '@' . $this->getFilterField($index, $filter->field) . ':[-inf (' . $this->escapeFilterValue($filter->value) . ']',
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = '@' . $this->getFilterField($index, $filter->field) . ':[-inf ' . $this->escapeFilterValue($filter->value) . ']',
                $filter instanceof Condition\GeoDistanceCondition => $filters[] = \sprintf(
                    '@%s:[%s %s %s]',
                    $this->getFilterField($index, $filter->field),
                    $filter->longitude,
                    $filter->latitude,
                    ($filter->distance / 1000) . ' km',
                ),
                $filter instanceof Condition\GeoBoundingBoxCondition => throw new \RuntimeException('Not supported by RediSearch: https://github.com/RediSearch/RediSearch/issues/680 or https://github.com/RediSearch/RediSearch/issues/5032'),
                /* Keep here for future implementation:
                $filter instanceof Condition\GeoBoundingBoxCondition => ($filters[] = \sprintf(
                    '@%s:[WITHIN $filter_%s]',
                    $this->getFilterField($index, $filter->field),
                    $key,
                )) && ($parameters['filter_' . $key] = \sprintf(
                    'POLYGON((%s %s, %s %s, %s %s, %s %s, %s %s))',
                    $filter->westLongitude,
                    $filter->northLatitude,
                    $filter->westLongitude,
                    $filter->southLatitude,
                    $filter->eastLongitude,
                    $filter->southLatitude,
                    $filter->eastLongitude,
                    $filter->northLatitude,
                    $filter->westLongitude,
                    $filter->northLatitude,
                )),
                */
                $filter instanceof Condition\AndCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, true, $parameters) . ')',
                $filter instanceof Condition\OrCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, false, $parameters) . ')',
                default => throw new \LogicException($filter::class . ' filter not implemented.'),
            };
        }

        if (\count($filters) < 2) {
            return \implode('', $filters);
        }

        return \implode($conjunctive ? ' ' : ' | ', $filters);
    }
}
