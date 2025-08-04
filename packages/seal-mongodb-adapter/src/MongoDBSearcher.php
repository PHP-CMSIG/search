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

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use MongoDB\Response\MongoDB;

final class MongoDBSearcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct(
        private readonly ClientWrapper $client,
    ) {
        $this->marshaller = new Marshaller(
            geoPointFieldConfig: [
                'latitude' => 'lat',
                'longitude' => 'lon',
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
            /** @var \MongoDB\Model\BSONDocument|null $searchResult */
            $searchResult = $this->client->getDatabase()
                ->getCollection($search->index->name)
                ->findOne(['_id' => $search->filters[0]->identifier]);

            if (null === $searchResult) {
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
        $options = [];

        if (0 !== $search->offset) {
            $options['skip'] = $search->offset;
        }

        if ($search->limit) {
            $options['limit'] = $search->limit;
        }

        foreach ($search->sortBys as $field => $direction) {
            $options['sort'] = [$field => 'asc' === $direction ? 1 : -1];
        }

        /** @var \MongoDB\Model\BSONDocument[] $searchResults */
        $searchResults = $this->client->getDatabase()
            ->getCollection($search->index->name)
            ->find($query, $options);

        // TODO implement queries following code was copied from elasticsearch

        /*

        if ([] === $query) {
            $query['match_all'] = new \stdClass();
        }

        $sort = [];
        foreach ($search->sortBys as $field => $direction) {
            $sort[] = [$field => $direction];
        }

        $body = [
            'sort' => $sort,
            'query' => $query,
        ];

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

        $response = $this->client->search([
            'index' => $search->index->name,
            'body' => $body,
        ]);
        */

        $searchResults = [...$searchResults];
        $total = \count([...$searchResults]); // TODO we need a real total here

        return new Result(
            $this->hitsToDocuments($search->index, $searchResults, $search->highlightFields),
            $total,
        );
    }

    /**
     * @param \MongoDB\Model\BSONDocument[] $hits
     * @param array<string> $highlightFields
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(Index $index, iterable $hits, array $highlightFields): \Generator
    {
        foreach ($hits as $hit) {
            $hit = \json_decode(\json_encode($hit, \JSON_THROW_ON_ERROR), true); // TODO check with mongodb team
            $identifier = $hit['_id'];
            unset($hit['_id']);
            $hit[$index->getIdentifierField()->name] = $identifier;

            $document = $this->marshaller->unmarshall($index->fields, $hit);

            if ([] === $highlightFields) {
                yield $document;

                continue;
            }

            // TODO implement highlighting

            yield $document;
        }
    }

    private function getFilterField(Index $index, string $name): string
    {
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
                $filter instanceof Condition\IdentifierCondition => $filterQueries[]['_id'][] = $filter->identifier,
                $filter instanceof Condition\SearchCondition => $filterQueries[]['$search'] =[
                    'index' => $index->name . '-search-index',
                    'text' => [
                        'query' => $filter->query,
                        'path' => $index->searchableFields,
                    ],
                ],
                $filter instanceof Condition\EqualCondition => $filterQueries[][$this->getFilterField($index, $filter->field)] = $filter->value,
                $filter instanceof Condition\NotEqualCondition => $filterQueries[][$this->getFilterField($index, $filter->field)]['$ne'] = $filter->value,
                $filter instanceof Condition\GreaterThanCondition => $filterQueries[][$this->getFilterField($index, $filter->field)]['$gt'] = $filter->value,
                $filter instanceof Condition\GreaterThanEqualCondition => $filterQueries[][$this->getFilterField($index, $filter->field)]['$gte'] = $filter->value,
                $filter instanceof Condition\LessThanCondition => $filterQueries[][$this->getFilterField($index, $filter->field)]['$lt'] = $filter->value,
                $filter instanceof Condition\LessThanEqualCondition => $filterQueries[][$this->getFilterField($index, $filter->field)]['$lte'] = $filter->value,
                $filter instanceof Condition\InCondition, => $filterQueries[][$this->getFilterField($index, $filter->field)]['$in'] = $filter->values,
                $filter instanceof Condition\NotInCondition => $filterQueries[][$this->getFilterField($index, $filter->field)]['$nin'] = $filter->values,
                $filter instanceof Condition\GeoDistanceCondition => $filterQueries[]['geo_distance'] = [
                    'distance' => $filter->distance,
                    $this->getFilterField($index, $filter->field) => [
                        'lat' => $filter->latitude,
                        'lon' => $filter->longitude,
                    ],
                ],
                $filter instanceof Condition\GeoBoundingBoxCondition => $filterQueries[]['geo_bounding_box'][$this->getFilterField($index, $filter->field)] = [
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
            $conjunctive ? '$or' : '$and' => $filterQueries,
        ];
    }
}
