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

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Facet\AbstractFacet;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Facet\MinMaxFacet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;

final class MeilisearchSearcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new Marshaller(
            dateFormat: 'U',
            geoPointFieldConfig: [
                'name' => '_geo',
                'latitude' => 'lat',
                'longitude' => 'lng',
            ],
        );
    }

    public function count(Index $index): int
    {
        return $this->client->index($index->name)->stats()['numberOfDocuments'] ?? 0;
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
                $data = $this->client->index($search->index->name)->getDocument($search->filters[0]->identifier);
            } catch (ApiException $e) {
                if (404 !== $e->httpStatus) {
                    throw $e;
                }

                return new Result(
                    $this->hitsToDocuments($search->index, [], [], $search->highlightPreTag),
                    0,
                );
            }

            return new Result(
                $this->hitsToDocuments($search->index, [$data], [], $search->highlightPreTag),
                1,
            );
        }

        $searchIndex = $this->client->index($search->index->name);

        $query = null;
        $filters = $this->recursiveResolveFilterConditions($search->index, $search->filters, true, $query);

        $searchParams = [];
        if ('' !== $filters) {
            $searchParams = ['filter' => $filters];
        }

        if (0 !== $search->offset) {
            $searchParams['offset'] = $search->offset;
        }

        if ($search->limit) {
            $searchParams['limit'] = $search->limit;
        }

        foreach ($search->sortBys as $field => $direction) {
            $searchParams['sort'][] = $field . ':' . $direction;
        }

        if ([] !== $search->highlightFields) {
            $searchParams['attributesToHighlight'] = $search->highlightFields;
            $searchParams['highlightPreTag'] = $search->highlightPreTag;
            $searchParams['highlightPostTag'] = $search->highlightPostTag;
        }

        if (null !== $search->distinct) {
            $searchParams['distinct'] = $search->distinct;
        }

        $searchParams['facets'] = \array_map(fn (AbstractFacet $facet) => $facet->field, $search->facets);

        $searchResult = $searchIndex->search($query, $searchParams);
        $data = $searchResult->toArray();

        /** @var array<string, array{min: float, max: float}> $facetStats */
        $facetStats = $searchResult->getFacetStats();
        /** @var array<string, array<string, int>> $facetDistribution */
        $facetDistribution = $searchResult->getFacetDistribution();

        return new Result(
            $this->hitsToDocuments($search->index, $data['hits'], $search->highlightFields, $search->highlightPreTag),
            $data['totalHits'] ?? $data['estimatedTotalHits'] ?? null,
            $this->formatFacets($facetStats, $facetDistribution, $search->facets),
        );
    }

    /**
     * @param iterable<array<string, mixed>> $hits
     * @param array<string> $highlightFields
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(Index $index, iterable $hits, array $highlightFields, string $highlightPreTag): \Generator
    {
        foreach ($hits as $hit) {
            $document = $this->marshaller->unmarshall($index->fields, $hit);

            if ([] === $highlightFields) {
                yield $document;

                continue;
            }

            $document['_formatted'] ??= [];

            \assert(
                \is_array($document['_formatted']),
                'Document with key "_formatted" expected to be array.',
            );

            foreach ($highlightFields as $highlightField) {
                \assert(
                    isset($hit['_formatted'])
                    && \is_array($hit['_formatted'])
                    && isset($hit['_formatted'][$highlightField]),
                    \sprintf('Expected highlight field "%s" to be available in the search hit.', $highlightField),
                );

                $value = $hit['_formatted'][$highlightField];

                if (!\is_string($value)
                    || !\str_contains($value, $highlightPreTag)
                ) {
                    $value = null;
                }

                $document['_formatted'][$highlightField] = $value;
            }

            yield $document;
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
     * @param list<string|int|float|bool> $value
     */
    private function escapeArrayFilterValues(array $value): string
    {
        return \implode(
            ', ',
            \array_map([$this, 'escapeFilterValue'], $value),
        );
    }

    /**
     * @param object[] $conditions
     */
    private function recursiveResolveFilterConditions(Index $index, array $conditions, bool $conjunctive, string|null &$query): string
    {
        $filters = [];

        foreach ($conditions as $filter) {
            if ($filter instanceof Condition\InCondition) {
                $filter = $filter->createOrCondition();
            }

            match (true) {
                $filter instanceof Condition\IdentifierCondition => $filters[] = $index->getIdentifierField()->name . ' = ' . $this->escapeFilterValue($filter->identifier),
                $filter instanceof Condition\SearchCondition => $query = $filter->query,
                $filter instanceof Condition\EqualCondition => $filters[] = $filter->field . ' = ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\NotEqualCondition => $filters[] = $filter->field . ' != ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\NotInCondition => $filters[] = $filter->field . ' NOT IN [' . $this->escapeArrayFilterValues(\array_map(fn ($value) => $this->convertValue($index, $filter->field, $value), $filter->values)) . ']',
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $filter->field . ' > ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $filter->field . ' >= ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\LessThanCondition => $filters[] = $filter->field . ' < ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $filter->field . ' <= ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\GeoDistanceCondition => $filters[] = \sprintf(
                    '_geoRadius(%s, %s, %s)',
                    $filter->latitude,
                    $filter->longitude,
                    $filter->distance,
                ),
                $filter instanceof Condition\GeoBoundingBoxCondition => $filters[] = \sprintf(
                    '_geoBoundingBox([%s, %s], [%s, %s])',
                    $filter->northLatitude,
                    $filter->eastLongitude,
                    $filter->southLatitude,
                    $filter->westLongitude,
                ),
                $filter instanceof Condition\AndCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, true, $query) . ')',
                $filter instanceof Condition\OrCondition => $filters[] = '(' . $this->recursiveResolveFilterConditions($index, $filter->conditions, false, $query) . ')',
                default => throw new \LogicException($filter::class . ' filter not implemented.'),
            };
        }

        if (\count($filters) < 2) {
            return \implode('', $filters);
        }

        return \implode($conjunctive ? ' AND ' : ' OR ', $filters);
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T|int
     */
    private function convertValue(Index $index, string $field, mixed $value): mixed
    {
        $field = $index->findFieldByPath($field);

        return match (true) {
            $field instanceof \CmsIg\Seal\Schema\Field\DateTimeField && \is_string($value) => \strtotime($value) ?: $value,
            default => $value,
        };
    }

    /**
     * @param array<AbstractFacet> $facets
     * @param array<string, array{min: float, max: float}> $facetStats
     * @param array<string, array<string, int>> $facetDistribution
     *
     * @return array<string, mixed>
     */
    private function formatFacets(array $facetStats, array $facetDistribution, array $facets): array
    {
        $formatted = [];

        foreach ($facets as $facet) {
            if ($facet instanceof MinMaxFacet && isset($facetStats[$facet->field])) {
                $formatted[$facet->field]['min'] = $facetStats[$facet->field]['min'];
                $formatted[$facet->field]['max'] = $facetStats[$facet->field]['max'];
                continue;
            }
            if ($facet instanceof CountFacet && isset($facetDistribution[$facet->field])) {
                $formatted[$facet->field]['count'] = $facetDistribution[$facet->field];
            }
        }

        return $formatted;
    }
}
