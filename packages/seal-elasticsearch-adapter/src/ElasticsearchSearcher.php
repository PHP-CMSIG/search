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

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Facet\AbstractFacet;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Facet\MinMaxFacet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;

final class ElasticsearchSearcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new Marshaller(
            geoPointFieldConfig: [
                'latitude' => 'lat',
                'longitude' => 'lon',
            ],
        );
    }

    public function count(Index $index): int
    {
        try {
            /** @var Elasticsearch $response */
            $response = $this->client->count([
                'index' => $index->name,
            ]);

            return $response->asArray()['count'] ?? 0; // @phpstan-ignore-line return.type
        } catch (ClientResponseException) {
            return 0;
        }
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
                /** @var Elasticsearch $response */
                $response = $this->client->get([
                    'index' => $search->index->name,
                    'id' => $search->filters[0]->identifier,
                ]);

                /** @var array<string, mixed> $searchResult */
                $searchResult = $response->asArray();
            } catch (ClientResponseException $e) {
                $response = $e->getResponse();
                if (404 !== $response->getStatusCode()) {
                    throw $e;
                }

                return new Result(
                    $this->hitsToDocuments($search->index, [], []),
                    0,
                );
            }

            return new Result(
                $this->hitsToDocuments($search->index, [$searchResult], []),
                1,
            );
        }

        $query = $this->recursiveResolveFilterConditions($search->index, $search->filters, true);

        if ([] === $query) {
            $query['match_all'] = new \stdClass();
        }

        $sort = [];
        foreach ($search->sortBys as $field => $direction) {
            $sort[] = [$this->getFilterField($search->index, $field) => $direction];
        }

        $body = [
            'sort' => $sort,
            'query' => $query,
        ];

        if (0 !== $search->offset) {
            $body['from'] = $search->offset;
        }

        if ($search->limit) {
            $body['size'] = $search->limit;
        }

        if ([] !== $search->highlightFields) {
            $highlightFields = [];
            foreach ($search->highlightFields as $highlightField) {
                $highlightFields[$highlightField] = [
                    'pre_tags' => [$search->highlightPreTag],
                    'post_tags' => [$search->highlightPostTag],
                ];
            }

            $body['highlight'] = [
                'fields' => $highlightFields,
            ];
        }

        if (null !== $search->distinct) {
            $body['collapse'] = [
                'field' => $this->getFilterField($search->index, $search->distinct),
            ];
        }

        foreach ($search->facets as $facet) {
            if ($facet instanceof CountFacet) {
                $body['aggs'][$facet->field . '_count']['terms']['field'] = $this->getFilterField($search->index, $facet->field);
            }
            if ($facet instanceof MinMaxFacet) {
                $body['aggs'][$facet->field . '_min']['min']['field'] = $this->getFilterField($search->index, $facet->field);
                $body['aggs'][$facet->field . '_max']['max']['field'] = $this->getFilterField($search->index, $facet->field);
            }
        }

        /** @var Elasticsearch $response */
        $response = $this->client->search([
            'index' => $search->index->name,
            'body' => $body,
        ]);

        /**
         * @var array{
         *     hits: array{
         *         hits: array<array<string, mixed>>,
         *         total: array{
         *            value: int
         *         }
         *     },
         *     aggregations?: array<string, array{
         *         value?: float|null,
         *         buckets?: array<array{
         *             key: string|int,
         *             doc_count: int
         *         }>
         *      }>
         * } $searchResult
         */
        $searchResult = $response->asArray();

        return new Result(
            $this->hitsToDocuments($search->index, $searchResult['hits']['hits'], $search->highlightFields),
            $searchResult['hits']['total']['value'],
            $this->formatFacets($searchResult['aggregations'] ?? [], $search->facets),
        );
    }

    /**
     * @param array<array<string, mixed>> $hits
     * @param array<string> $highlightFields
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(Index $index, array $hits, array $highlightFields): \Generator
    {
        /** @var array{_index: string, _source: array<string, mixed>, highlight?: mixed} $hit */
        foreach ($hits as $hit) {
            $document = $this->marshaller->unmarshall($index->fields, $hit['_source']);

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
                // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
                $document['_formatted'][$highlightField] = $hit['highlight'][$highlightField][0]
                    ?? null;
            }

            yield $document;
        }
    }

    private function getFilterField(Index $index, string $name): string
    {
        $field = $index->getFieldByPath($name);

        if ($field instanceof Field\TextField) {
            return $name . '.raw';
        }

        return $name;
    }

    /**
     * @param object[] $filters
     *
     * @return array<string|int, mixed>
     */
    private function recursiveResolveFilterConditions(Index $index, array $filters, bool $conjunctive): array
    {
        $filterQueries = [];

        foreach ($filters as $filter) {
            match (true) {
                $filter instanceof Condition\IdentifierCondition => $filterQueries[]['ids']['values'][] = $filter->identifier, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\SearchCondition => $filterQueries[]['bool']['must']['query_string']['query'] = $filter->query, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\EqualCondition => $filterQueries[]['term'][$this->getFilterField($index, $filter->field)]['value'] = $filter->value, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\NotEqualCondition => $filterQueries[]['bool']['must_not']['term'][$this->getFilterField($index, $filter->field)]['value'] = $filter->value, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\GreaterThanCondition => $filterQueries[]['range'][$this->getFilterField($index, $filter->field)]['gt'] = $filter->value, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\GreaterThanEqualCondition => $filterQueries[]['range'][$this->getFilterField($index, $filter->field)]['gte'] = $filter->value, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\LessThanCondition => $filterQueries[]['range'][$this->getFilterField($index, $filter->field)]['lt'] = $filter->value, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\LessThanEqualCondition => $filterQueries[]['range'][$this->getFilterField($index, $filter->field)]['lte'] = $filter->value, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\InCondition, => $filterQueries[]['terms'][$this->getFilterField($index, $filter->field)] = $filter->values, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\NotInCondition => $filterQueries[]['bool']['must_not']['terms'][$this->getFilterField($index, $filter->field)] = $filter->values, // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                $filter instanceof Condition\GeoDistanceCondition => $filterQueries[]['geo_distance'] = [ // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                    'distance' => $filter->distance,
                    $this->getFilterField($index, $filter->field) => [
                        'lat' => $filter->latitude,
                        'lon' => $filter->longitude,
                    ],
                ],
                $filter instanceof Condition\GeoBoundingBoxCondition => $filterQueries[]['geo_bounding_box'][$this->getFilterField($index, $filter->field)] = [ // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
                    'top_left' => [
                        'lat' => $filter->northLatitude,
                        'lon' => $filter->westLongitude,
                    ],
                    'bottom_right' => [
                        'lat' => $filter->southLatitude,
                        'lon' => $filter->eastLongitude,
                    ],
                ],
                $filter instanceof Condition\AndCondition => $filterQueries[] = $this->recursiveResolveFilterConditions($index, $filter->conditions, true),
                $filter instanceof Condition\OrCondition => $filterQueries[] = $this->recursiveResolveFilterConditions($index, $filter->conditions, false),
                default => throw new \LogicException($filter::class . ' filter not implemented.'),
            };
        }

        if (\count($filterQueries) <= 1) {
            return $filterQueries[0] ?? [];
        }

        return [
            'bool' => [
                $conjunctive ? 'must' : 'should' => $filterQueries,
            ],
        ];
    }

    /**
     * @param array<string, array{
     *          value?: float|null,
     *          buckets?: array<array{
     *              key: string|int,
     *              key_as_string?: string,
     *              doc_count: int
     *          }>
     *       }> $aggregations
     * @param array<AbstractFacet> $facets
     *
     * @return array<string, mixed>
     */
    private function formatFacets(array $aggregations, array $facets): array
    {
        $formatted = [];

        foreach ($facets as $facet) {
            if ($facet instanceof MinMaxFacet && isset($aggregations[$facet->field . '_min']['value']) && isset($aggregations[$facet->field . '_max']['value'])) {
                $formatted[$facet->field]['min'] = $aggregations[$facet->field . '_min']['value'];
                $formatted[$facet->field]['max'] = $aggregations[$facet->field . '_max']['value'];
                continue;
            }
            if ($facet instanceof CountFacet && isset($aggregations[$facet->field . '_count']['buckets'])) {
                foreach ($aggregations[$facet->field . '_count']['buckets'] as $bucket) {
                    $key = (string) ($bucket['key_as_string'] ?? $bucket['key']);
                    $formatted[$facet->field]['count'][$key] = $bucket['doc_count'];
                }
            }
        }

        return $formatted;
    }
}
