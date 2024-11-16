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
            dateAsInteger: true,
            separator: LoupeHelper::SEPARATOR,
            sourceField: LoupeHelper::SOURCE_FIELD,
            geoPointFieldConfig: [
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
            $loupe = $this->loupeHelper->getLoupe($search->index);
            $data = $loupe->getDocument($search->filters[0]->identifier);

            if (!$data) {
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

        if ($search->limit) {
            $searchParameters = $searchParameters->withHitsPerPage($search->limit);
        }

        if ($search->offset && $search->limit && 0 === ($search->offset % $search->limit)) {
            $searchParameters = $searchParameters->withPage((int) (($search->offset / $search->limit) + 1));
        } elseif (null !== $search->limit && 0 !== $search->offset) {
            throw new \RuntimeException('None paginated limit and offset not supported. See https://github.com/loupe-php/loupe/issues/13');
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
            $this->hitsToDocuments($search->index, $result->getHits()),
            $result->getTotalHits(),
        );
    }

    private function escapeFilterValue(string|int|float|bool $value): string
    {
        return SearchParameters::escapeFilterValue($value);
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
                $filter instanceof Condition\EqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' = ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\NotEqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' != ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' > ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' >= ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' < ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' <= ' . $this->escapeFilterValue($filter->value),
                $filter instanceof Condition\InCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' IN (' . \implode(', ', \array_map(fn ($value) => $this->escapeFilterValue($value), $filter->values)) . ')',
                $filter instanceof Condition\NotInCondition => $filters[] = $this->loupeHelper->formatField($filter->field) . ' NOT IN (' . \implode(', ', \array_map(fn ($value) => $this->escapeFilterValue($value), $filter->values)) . ')',
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
}
