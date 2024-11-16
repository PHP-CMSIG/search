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

namespace CmsIg\Seal\Adapter\Algolia;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\AlgoliaSearch\Exceptions\NotFoundException;
use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;

final class AlgoliaSearcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly SearchClient $client,
    ) {
        $this->marshaller = new Marshaller(
            geoPointFieldConfig: [
                'name' => '_geoloc',
                'latitude' => 'lat',
                'longitude' => 'lng',
            ],
        );
    }

    public function search(Search $search): Result
    {
        // optimized single document query
        if (
            1 === \count($search->filters)
            && $search->filters[0] instanceof Condition\IdentifierCondition
            && 0 === $search->offset
            && 1 === $search->limit
        ) {
            try {
                /** @var array<string, mixed> $data */
                $data = $this->client->getObject(
                    $search->index->name,
                    $search->filters[0]->identifier,
                );
            } catch (NotFoundException) {
                return new Result(
                    $this->hitsToDocuments($search->index, []),
                    0,
                );
            }

            return new Result(
                $this->hitsToDocuments($search->index, [$data]),
                1,
            );
        }

        if (\count($search->sortBys) > 1) {
            throw new \RuntimeException('Algolia Adapter does not yet support search multiple indexes: https://github.com/php-cmsig/search/issues/41');
        }

        $indexName = $search->index->name;

        $sortByField = \array_key_first($search->sortBys);
        if ($sortByField) {
            $indexName .= '__' . \str_replace('.', '_', $sortByField) . '_' . $search->sortBys[$sortByField];
        }

        $query = '';
        $geoFilters = [];
        $filters = $this->recursiveResolveFilterConditions($search->index, $search->filters, true, $query, $geoFilters);

        $searchParams = [];
        if ('' !== $filters) {
            // Algolia does not like useless brackets around the topmost group so we remove them if present
            $filters = \preg_replace('#(^\(|\)$)#', '', $filters);

            $searchParams = ['filters' => $filters];
        }

        if ([] !== $geoFilters) {
            $searchParams += $geoFilters;
        }

        if (0 !== $search->offset) {
            $searchParams['offset'] = $search->offset;
        }

        if ($search->limit) {
            $searchParams['length'] = $search->limit;
            $searchParams['offset'] ??= 0; // length would be ignored without offset see: https://www.algolia.com/doc/api-reference/api-parameters/length/
        }

        if ('' !== $query) {
            $searchParams['query'] = $query;
        }

        $data = $this->client->searchSingleIndex($indexName, $searchParams);
        \assert(\is_array($data) && isset($data['hits']) && \is_array($data['hits']), 'The "hits" array is expected to be returned by algolia client.');
        \assert(isset($data['nbHits']) && \is_int($data['nbHits']), 'The "nbHits" value is expected to be returned by algolia client.');

        return new Result(
            $this->hitsToDocuments($search->index, $data['hits']),
            $data['nbHits'] ?? null, // @phpstan-ignore-line
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
            // remove Algolia Metadata
            unset($hit['objectID']);
            unset($hit['_highlightResult']);

            yield $this->marshaller->unmarshall($index->fields, $hit);
        }
    }

    private function escapeFilterValue(string|int|float|bool $value): string
    {
        return match (true) {
            \is_string($value) => '"' . \addslashes($value) . '"',
            \is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    /**
     * @param object[] $conditions
     * @param object[] $geoFilters
     */
    private function recursiveResolveFilterConditions(Index $index, array $conditions, bool $conjunctive, string|null &$query, array &$geoFilters): string
    {
        $filters = [];

        foreach ($conditions as $filter) {
            $filter = match (true) {
                $filter instanceof Condition\InCondition => $filter->createOrCondition(),
                $filter instanceof Condition\NotInCondition => $filter->createAndCondition(),
                default => $filter,
            };

            match (true) {
                $filter instanceof Condition\IdentifierCondition => $filters[] = $index->getIdentifierField()->name . ':' . $this->escapeFilterValue($filter->identifier),
                $filter instanceof Condition\SearchCondition => $query = $filter->query,
                $filter instanceof Condition\EqualCondition => $filters[] = $filter->field . ':' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\NotEqualCondition => $filters[] = 'NOT ' . $filter->field . ':' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $filter->field . ' > ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $filter->field . ' >= ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanCondition => $filters[] = $filter->field . ' < ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $filter->field . ' <= ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GeoDistanceCondition => $geoFilters = [
                    'aroundLatLng' => \sprintf(
                        '%s, %s',
                        $this->escapeFilterValue($filter->latitude),
                        $this->escapeFilterValue($filter->longitude),
                    ),
                    'aroundRadius' => $filter->distance,
                ],
                $filter instanceof Condition\GeoBoundingBoxCondition => $geoFilters = [
                    'insideBoundingBox' => [[$filter->northLatitude, $filter->westLongitude, $filter->southLatitude, $filter->eastLongitude]],
                ],
                $filter instanceof Condition\AndCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, true, $query, $geoFilters) . ')',
                $filter instanceof Condition\OrCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, false, $query, $geoFilters) . ')',
                default => throw new \LogicException($filter::class . ' filter not implemented.'),
            };
        }

        if (\count($filters) < 2) {
            return \implode('', $filters);
        }

        return \implode($conjunctive ? ' AND ' : ' OR ', $filters);
    }
}
