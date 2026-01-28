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

namespace CmsIg\Seal\Adapter\Loupe;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\FlattenMarshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Facet\AbstractFacet;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Facet\MinMaxFacet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use Loupe\Loupe\SearchParameters;

final class LoupeSearcher implements SearcherInterface
{
    private readonly FlattenMarshaller $marshaller;

    public function __construct(
        private readonly LoupeHelper $loupeHelper,
    ) {
        $this->marshaller = new FlattenMarshaller(
            dateFormat: 'U',
            geoPointFieldConfig: [
                'latitude' => 'lat',
                'longitude' => 'lng',
            ],
            fieldSeparator: LoupeHelper::SEPARATOR,
        );
    }

    public function count(Index $index): int
    {
        $loupe = $this->loupeHelper->getLoupe($index);

        return $loupe->countDocuments();
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
            $loupe = $this->loupeHelper->getLoupe($search->index);
            $data = $loupe->getDocument($search->filters[0]->identifier);

            if (!$data) {
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

        $loupe = $this->loupeHelper->getLoupe($search->index);

        $searchParameters = SearchParameters::create();

        $query = null;
        $filters = $this->recursiveResolveFilterConditions($search->index, $search->filters, true, $query);

        if ($query) {
            $searchParameters = $searchParameters->withQuery($query);
        }

        if ('' !== $filters) {
            $searchParameters = $searchParameters->withFilter($filters);
        }

        if (0 !== $search->offset) {
            $searchParameters = $searchParameters->withOffset($search->offset);
        }

        if ($search->limit) {
            $searchParameters = $searchParameters->withLimit($search->limit);
        }

        if ($search->distinct) {
            $searchParameters = $searchParameters->withDistinct($search->distinct);
        }

        $searchParameters = $searchParameters->withFacets(\array_map(static fn (AbstractFacet $facet) => $facet->field, $search->facets));

        if ([] !== $search->highlightFields) {
            $searchParameters = $searchParameters->withAttributesToHighlight(
                $search->highlightFields,
                $search->highlightPreTag,
                $search->highlightPostTag,
            );
        }

        $sorts = [];
        foreach ($search->sortBys as $field => $direction) {
            $sorts[] = $this->loupeHelper->formatField($field) . ':' . $direction;
        }

        if ([] !== $sorts) {
            $searchParameters = $searchParameters->withSort($sorts);
        }

        $result = $loupe->search($searchParameters);

        return new Result(
            $this->hitsToDocuments($search->index, $result->getHits(), $search->highlightFields, $search->highlightPreTag),
            $result->getTotalHits(),
            $this->formatFacets($result->getFacetStats(), $result->getFacetDistribution(), $search->facets),
        );
    }

    private function escapeFilterValue(string|int|float|bool $value): string
    {
        return SearchParameters::escapeFilterValue($value);
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

    /**
     * @param object[] $conditions
     */
    private function recursiveResolveFilterConditions(Index $index, array $conditions, bool $conjunctive, string|null &$query): string
    {
        $filters = [];

        foreach ($conditions as $filter) {
            match (true) {
                $filter instanceof Condition\IdentifierCondition => $filters[] = $index->getIdentifierField()->name . ' = ' . $this->escapeFilterValue($filter->identifier),
                $filter instanceof Condition\SearchCondition => $query = $filter->query,
                $filter instanceof Condition\EqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' = ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\NotEqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' != ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' > ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' >= ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\LessThanCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' < ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' <= ' . $this->escapeFilterValue($this->convertValue($index, $filter->field, $filter->value)),
                $filter instanceof Condition\InCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' IN (' . \implode(', ', \array_map(fn ($value) => $this->escapeFilterValue($this->convertValue($index, $filter->field, $value)), $filter->values)) . ')',
                $filter instanceof Condition\NotInCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' NOT IN (' . \implode(', ', \array_map(fn ($value) => $this->escapeFilterValue($this->convertValue($index, $filter->field, $value)), $filter->values)) . ')',
                $filter instanceof Condition\GeoDistanceCondition => $filters[] = \sprintf(
                    '_geoRadius(%s, %s, %s, %s)',
                    $this->loupeHelper->formatField($filter->field),
                    $filter->latitude,
                    $filter->longitude,
                    $filter->distance,
                ),
                $filter instanceof Condition\GeoBoundingBoxCondition => $filters[] = \sprintf(
                    '_geoBoundingBox(%s, %s, %s, %s, %s)',
                    $this->loupeHelper->formatField($filter->field),
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
     * @param array<string, array<string, float>> $facetStats
     * @param array<string, array<string, int>> $facetDistribution
     * @param array<AbstractFacet> $facets
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
