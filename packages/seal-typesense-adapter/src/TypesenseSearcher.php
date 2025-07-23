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

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Facet\AbstractFacet;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Facet\MinMaxFacet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

final class TypesenseSearcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new Marshaller(
            dateAsInteger: true,
            geoPointFieldConfig: [
                'latitude' => 0,
                'longitude' => 1,
            ],
        );
    }

    public function count(Index $index): int
    {
        return $this->client->collections[$index->name]->retrieve()['num_documents'] ?? 0;
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
                $data = $this->client->collections[$search->index->name]->documents[$search->filters[0]->identifier]->retrieve();
            } catch (ObjectNotFound) {
                return new Result(
                    $this->hitsToDocuments($search->index, [], []),
                    0,
                );
            }

            return new Result(
                $this->hitsToDocuments($search->index, [['document' => $data]], []),
                1,
            );
        }

        $searchParams = [
            'q' => '',
            'query_by' => \implode(',', $search->index->searchableFields),
        ];

        $query = null;
        $filters = $this->recursiveResolveFilterConditions($search->index, $search->filters, true, $query);

        if (null !== $query) {
            $searchParams['q'] = $query;
        }

        if ('' !== $filters) {
            $searchParams['filter_by'] = $filters;
        }

        if (0 !== $search->offset) {
            $searchParams['page'] = ($search->offset / $search->limit) + 1;
        }

        if ($search->limit) {
            $searchParams['per_page'] = $search->limit;
        }

        $sortBys = [];
        foreach ($search->sortBys as $field => $direction) {
            $sortBys[] = $field . ':' . $direction;
        }

        if ([] !== $sortBys) {
            $searchParams['sort_by'] = \implode(',', $sortBys);
        }

        if ([] !== $search->highlightFields) {
            $searchParams['highlight_fields'] = \implode(', ', $search->highlightFields);
            $searchParams['highlight_start_tag'] = $search->highlightPreTag;
            $searchParams['highlight_end_tag'] = $search->highlightPostTag;
        }

        $isGrouped = false;

        if (null !== $search->distinct) {
            $searchParams['group_by'] = $search->distinct;
            $searchParams['group_limit'] = '1';
            $isGrouped = true;
        }

        $searchParams['facet_by'] = \implode(',', \array_map(fn (AbstractFacet $facet) => $facet->field, $search->facets));

        $data = $this->client->collections[$search->index->name]->documents->search($searchParams);

        /** @var array<int, array{
         *     field_name: string,
         *     counts: array<int, array{
         *         value: string,
         *         count: int
         *     }>,
         *     stats?: array{
         *         avg?: float,
         *         max?: float|int,
         *         min?: float|int,
         *         sum?: float|int
         *     }
         * }> $facetCounts
         */
        $facetCounts = $data['facet_counts'] ?? [];

        return new Result(
            $this->hitsToDocuments($search->index, $isGrouped ? $data['grouped_hits'][0]['hits'] : $data['hits'], $search->highlightFields),
            $data['found'] ?? null,
            $this->formatFacets($facetCounts, $search->facets),
        );
    }

    /**
     * @param iterable<array<string, mixed>> $hits
     * @param array<string> $highlightFields
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(Index $index, iterable $hits, array $highlightFields): \Generator
    {
        /** @var array{document: array<string, mixed>, highlight?: array<string, array{snippet: string}>} $hit */
        foreach ($hits as $hit) {
            $document = $this->marshaller->unmarshall($index->fields, $hit['document']);

            if ([] === $highlightFields) {
                yield $document;

                continue;
            }

            $document['_formatted'] ??= [];

            \assert(
                \is_array($document['_formatted']),
                'Document with key "_formatted" expected to be array.',
            );

            $hit['highlight'] ??= [];

            \assert(
                \is_array($hit['highlight']),
                'Hit with key "highlight" expected to be array.',
            );

            foreach ($highlightFields as $highlightField) {
                $document['_formatted'][$highlightField] = $hit['highlight'][$highlightField]['snippet']
                    ?? null;
            }

            yield $document;
        }
    }

    private function escapeFilterValue(string|int|float|bool $value): string
    {
        return match (true) {
            \is_string($value) => '"' . \addcslashes($value, '"&') . '"', // TODO escape?
            \is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    /**
     * @param object[] $conditions
     */
    private function recursiveResolveFilterConditions(Index $index, array $conditions, bool $conjunctive, string|null &$query): string
    {
        $filters = [];

        foreach ($conditions as $filter) {
            $filter = match (true) {
                $filter instanceof Condition\InCondition => $filter->createOrCondition(),
                $filter instanceof Condition\NotInCondition => $filter->createAndCondition(),
                default => $filter,
            };

            match (true) {
                $filter instanceof Condition\IdentifierCondition => $filters[] = 'id:=' . $this->escapeFilterValue($filter->identifier),
                $filter instanceof Condition\SearchCondition => $query = $filter->query,
                $filter instanceof Condition\EqualCondition => $filters[] = $filter->field . ':=' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\NotEqualCondition => $filters[] = $filter->field . ':!=' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $filter->field . ':>' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $filter->field . ':>=' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanCondition => $filters[] = $filter->field . ':<' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $filter->field . ':<=' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GeoDistanceCondition => $filters[] = \sprintf(
                    '%s:(%s, %s, %s)',
                    $filter->field,
                    $filter->latitude,
                    $filter->longitude,
                    ($filter->distance / 1000) . ' km', // convert to km
                ),
                $filter instanceof Condition\GeoBoundingBoxCondition => $filters[] = \sprintf(
                    '%s:(%s, %s, %s, %s, %s, %s, %s, %s)',
                    $filter->field,
                    // TODO recheck if polygon is bigger as half of the earth if it not accidentally switches
                    $filter->northLatitude,
                    $filter->eastLongitude,
                    $filter->southLatitude,
                    $filter->eastLongitude,
                    $filter->southLatitude,
                    $filter->westLongitude,
                    $filter->northLatitude,
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

        return \implode($conjunctive ? ' && ' : ' || ', $filters);
    }

    /**
     * @param array<int, array{
     *      field_name: string,
     *      counts: array<int, array{
     *          value: string,
     *          count: int
     *      }>,
     *      stats?: array{
     *          avg?: float,
     *          max?: float|int,
     *          min?: float|int,
     *          sum?: float|int
     *      }
     *  }> $facetCounts
     * @param array<AbstractFacet> $facets
     *
     * @return array<string, mixed>
     */
    private function formatFacets(array $facetCounts, array $facets): array
    {
        $facetInfoPerField = [];

        foreach ($facetCounts as $facetCount) {
            $count = [];

            foreach ($facetCount['counts'] as $data) {
                $count[$data['value']] = $data['count'];
            }

            $facetInfoPerField[$facetCount['field_name']] = [
                'count' => $count,
                'min' => $facetCount['stats']['min'] ?? null,
                'max' => $facetCount['stats']['max'] ?? null,
            ];
        }

        $formatted = [];

        foreach ($facets as $facet) {
            if ($facet instanceof MinMaxFacet && isset($facetInfoPerField[$facet->field])) {
                $formatted[$facet->field]['min'] = $facetInfoPerField[$facet->field]['min'];
                $formatted[$facet->field]['max'] = $facetInfoPerField[$facet->field]['max'];
                continue;
            }
            if ($facet instanceof CountFacet && isset($facetInfoPerField[$facet->field])) {
                $formatted[$facet->field]['count'] = $facetInfoPerField[$facet->field]['count'];
            }
        }

        return $formatted;
    }
}
