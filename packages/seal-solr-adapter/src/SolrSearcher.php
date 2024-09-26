<?php

declare(strict_types=1);

/*
 * This file is part of the Schranz Search package.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Schranz\Search\SEAL\Adapter\Solr;

use Schranz\Search\SEAL\Adapter\SearcherInterface;
use Schranz\Search\SEAL\Marshaller\FlattenMarshaller;
use Schranz\Search\SEAL\Schema\Exception\FieldByPathNotFoundException;
use Schranz\Search\SEAL\Schema\Field;
use Schranz\Search\SEAL\Schema\Index;
use Schranz\Search\SEAL\Search\Condition;
use Schranz\Search\SEAL\Search\Result;
use Schranz\Search\SEAL\Search\Search;
use Solarium\Client;
use Solarium\Component\Result\Highlighting\Highlighting;
use Solarium\Core\Query\DocumentInterface;

final class SolrSearcher implements SearcherInterface
{
    private readonly FlattenMarshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new FlattenMarshaller(
            addRawFilterTextField: true,
            geoPointFieldConfig: [
                'latitude' => 0,
                'longitude' => 1,
                'separator' => ',',
                'multiple' => false,
            ],
        );
    }

    public function search(Search $search): Result
    {
        // optimized single document query
        if (
            1 === \count($search->indexes)
            && 1 === \count($search->filters)
            && $search->filters[0] instanceof Condition\IdentifierCondition
            && 0 === $search->offset
            && 1 === $search->limit
        ) {
            $this->client->getEndpoint()
                ->setCollection($search->indexes[\array_key_first($search->indexes)]->name);

            $query = $this->client->createRealtimeGet();
            $query->addId($search->filters[0]->identifier);
            $result = $this->client->realtimeGet($query);

            if (!$result->getNumFound()) {
                return new Result(
                    $this->hitsToDocuments($search->indexes, []),
                    0,
                );
            }

            return new Result(
                $this->hitsToDocuments($search->indexes, [$result->getDocument()]),
                1,
            );
        }

        if (1 !== \count($search->indexes)) {
            throw new \RuntimeException('Solr does not yet support search multiple indexes: https://github.com/schranz-search/schranz-search/issues/86');
        }

        $index = $search->indexes[\array_key_first($search->indexes)];
        $this->client->getEndpoint()
            ->setCollection($index->name);

        $query = $this->client->createSelect();

        $queryText = null;

        $filters = [];
        foreach ($search->filters as $filter) {
            match (true) {
                $filter instanceof Condition\SearchCondition => $queryText = $filter->query,
                $filter instanceof Condition\IdentifierCondition => $filters[] = $index->getIdentifierField()->name . ':' . $this->escapeFilterValue($filter->identifier),
                $filter instanceof Condition\EqualCondition => $filters[] = $this->getFilterField($search->indexes, $filter->field) . ':' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\NotEqualCondition => $filters[] = '-' . $this->getFilterField($search->indexes, $filter->field) . ':' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $this->getFilterField($search->indexes, $filter->field) . ':{' . $this->escapeFilterValue($filter->value) . ' TO *}',
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $this->getFilterField($search->indexes, $filter->field) . ':[' . $this->escapeFilterValue($filter->value) . ' TO *]',
                $filter instanceof Condition\LessThanCondition => $filters[] = $this->getFilterField($search->indexes, $filter->field) . ':{* TO ' . $this->escapeFilterValue($filter->value) . '}',
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $this->getFilterField($search->indexes, $filter->field) . ':[* TO ' . $this->escapeFilterValue($filter->value) . ']',
                $filter instanceof Condition\GeoDistanceCondition => $filters[] = \sprintf(
                    '{!geofilt sfield=%s pt=%s,%s d=%s}',
                    $this->getFilterField($search->indexes, $filter->field),
                    $filter->latitude,
                    $filter->longitude,
                    $filter->distance / 1000, // Convert meters to kilometers
                ),
                $filter instanceof Condition\GeoBoundingBoxCondition => $filters[] = \sprintf(
                    '%s:[%s,%s TO %s,%s]', // docs: https://cwiki.apache.org/confluence/pages/viewpage.action?pageId=120723285#SolrAdaptersForLuceneSpatial4-Search
                    $this->getFilterField($search->indexes, $filter->field),
                    $filter->southLatitude,
                    $filter->westLongitude,
                    $filter->northLatitude,
                    $filter->eastLongitude,
                ),
                default => throw new \LogicException($filter::class . ' filter not implemented.'),
            };
        }

        if (null !== $queryText) {
            $dismax = $query->getDisMax();
            $dismax->setQueryFields(\implode(' ', $index->searchableFields));

            $query->setQuery($queryText);
        }

        foreach ($filters as $key => $filter) {
            $query->createFilterQuery('filter_' . $key)->setQuery($filter);
        }

        if (0 !== $search->offset) {
            $query->setStart($search->offset);
        }

        if ($search->limit) {
            $query->setRows($search->limit);
        }

        foreach ($search->sortBys as $field => $direction) {
            $query->addSort($field, $direction);
        }

        if ([] !== $search->highlightFields) {
            $highlighting = $query->getHighlighting();
            $highlighting->setFields(\implode(', ', $search->highlightFields));
            $highlighting->setSimplePrefix($search->highlightPreTag);
            $highlighting->setSimplePostfix($search->highlightPostTag);
        }

        $result = $this->client->select($query);

        return new Result(
            $this->hitsToDocuments($search->indexes, $result->getDocuments(), $result->getHighlighting()),
            (int) $result->getNumFound(),
        );
    }

    /**
     * @param Index[] $indexes
     * @param iterable<DocumentInterface> $hits
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function hitsToDocuments(array $indexes, iterable $hits, ?Highlighting $highlighting = null): \Generator
    {
        $index = $indexes[\array_key_first($indexes)];

        foreach ($hits as $hit) {
            /** @var array<string, mixed> $hit */
            $hit = $hit->getFields();

            unset($hit['_version_']);
            $identifierFieldName = $index->getIdentifierField()->name;

            if ('id' !== $identifierFieldName) {
                // Solr currently does not support set another identifier then id: https://github.com/schranz-search/schranz-search/issues/87
                $id = $hit['id'];
                unset($hit['id']);

                $hit[$identifierFieldName] = $id;
            }

            $document = $this->marshaller->unmarshall($index->fields, $hit);

            if ($highlighting instanceof \Solarium\Component\Result\Highlighting\Highlighting) {
                $highlightResult = $highlighting->getResult($hit[$identifierFieldName]);
                \assert(
                    $highlightResult instanceof \Solarium\Component\Result\Highlighting\Result,
                    'Expected the highlighting exists.',
                );

                $document['_formatted'] ??= [];

                \assert(
                    \is_array($document['_formatted']),
                    'Document with key "_formatted" expected to be array.',
                );

                foreach ($highlightResult->getFields() as $key => $value) {
                    $fieldConfig = $index->getFieldByPath($key);
                    // even non-multiple fields are returned as array we need to convert them to string
                    if (!$fieldConfig->multiple && \is_array($value)) {
                        $value = \implode(' ', $value);
                    }

                    $document['_formatted'][$key] = $value;
                }
            }

            yield $document;
        }
    }

    private function escapeFilterValue(string|int|float|bool $value): string
    {
        return '"' . \addcslashes((string) $value, '"+-&|!(){}[]^~*?:\\/ ') . '"';
    }

    /**
     * @param Index[] $indexes
     */
    private function getFilterField(array $indexes, string $name): string
    {
        foreach ($indexes as $index) {
            try {
                $field = $index->getFieldByPath($name);

                if ($field instanceof Field\TextField) {
                    return $name . '.raw';
                }

                return $name;
            } catch (FieldByPathNotFoundException) {
                // ignore when field is not found and use go to next index instead
            }
        }

        return $name;
    }
}
