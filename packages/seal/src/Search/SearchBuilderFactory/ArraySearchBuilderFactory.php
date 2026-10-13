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

namespace CmsIg\Seal\Search\SearchBuilderFactory;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Search\Condition\AbstractGroupCondition;
use CmsIg\Seal\Search\Condition\AndCondition;
use CmsIg\Seal\Search\Condition\EqualCondition;
use CmsIg\Seal\Search\Condition\GeoBoundingBoxCondition;
use CmsIg\Seal\Search\Condition\GeoDistanceCondition;
use CmsIg\Seal\Search\Condition\GreaterThanCondition;
use CmsIg\Seal\Search\Condition\GreaterThanEqualCondition;
use CmsIg\Seal\Search\Condition\IdentifierCondition;
use CmsIg\Seal\Search\Condition\InCondition;
use CmsIg\Seal\Search\Condition\LessThanCondition;
use CmsIg\Seal\Search\Condition\LessThanEqualCondition;
use CmsIg\Seal\Search\Condition\NotEqualCondition;
use CmsIg\Seal\Search\Condition\NotInCondition;
use CmsIg\Seal\Search\Condition\OrCondition;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Facet\AbstractFacet;
use CmsIg\Seal\Search\Facet\CountFacet;
use CmsIg\Seal\Search\Facet\MinMaxFacet;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Search\TypeUtil;

final class ArraySearchBuilderFactory
{
    public function __construct(
        private readonly EngineInterface $engine,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function build(string $index, SearchBuilderFactoryConfig $config, array $data): SearchBuilder
    {
        $searchBuilder = $this->engine->createSearchBuilder($index);
        $searchIndex = $searchBuilder->getSearch()->index;

        foreach (TypeUtil::readArrayByKey($data, 'filters', []) as $filter) {
            $deserializedFilter = $this->deserializeCondition(TypeUtil::ensureStringKeyedArrayValue($filter, 'filter'));
            $config->validateCondition($deserializedFilter, $searchIndex);
            $searchBuilder->addFilter($deserializedFilter);
        }

        foreach (TypeUtil::readArrayByKey($data, 'sortBys', []) as $field => $direction) {
            if (!\is_string($field) || !\is_string($direction) || !\in_array($direction, ['asc', 'desc'], true)) {
                throw new \InvalidArgumentException('Search builder sortBys must be a map of field names to "asc" or "desc".');
            }

            $config->validateSort($field, $searchIndex);
            $searchBuilder->addSortBy($field, $direction);
        }

        $limit = TypeUtil::readNullableNonNegativeIntByKey($data, 'limit');
        $config->validateLimit($limit);
        if (null !== $limit) {
            $searchBuilder->limit($limit);
        }

        $searchBuilder->offset(TypeUtil::readNonNegativeIntByKey($data, 'offset'));

        $highlight = TypeUtil::readStringKeyedArrayByKey($data, 'highlight', []);
        $highlightFields = TypeUtil::readStringListByKey($highlight, 'fields', []);
        foreach ($highlightFields as $field) {
            $config->validateHighlight($field, $searchIndex);
        }

        $searchBuilder->highlight(
            $highlightFields,
            TypeUtil::readStringByKey($highlight, 'preTag', '<mark>'),
            TypeUtil::readStringByKey($highlight, 'postTag', '</mark>'),
        );

        $distinct = TypeUtil::readNullableStringByKey($data, 'distinct');
        if (null !== $distinct) {
            $config->validateDistinct($distinct, $searchIndex);
        }

        $searchBuilder->distinct($distinct);

        foreach (TypeUtil::readArrayByKey($data, 'facets', []) as $facet) {
            $deserializedFacet = $this->deserializeFacet(TypeUtil::ensureStringKeyedArrayValue($facet, 'facet'));
            $config->validateFacet($deserializedFacet, $searchIndex);
            $searchBuilder->addFacet($deserializedFacet);
        }

        return $searchBuilder;
    }

    public function buildFromJson(string $index, SearchBuilderFactoryConfig $config, string $json): SearchBuilder
    {
        $data = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($data)) {
            throw new \InvalidArgumentException('Search builder JSON must decode to an object.');
        }

        /** @var array<string, mixed> $data */
        return $this->build($index, $config, $data);
    }

    /**
     * @return array{
     *     filters: array<array<string, mixed>>,
     *     sortBys: array<string, 'asc'|'desc'>,
     *     limit: int|null,
     *     offset: int,
     *     highlight: array{fields: array<string>, preTag: string, postTag: string},
     *     distinct: string|null,
     *     facets: array<array<string, mixed>>,
     * }
     */
    public function toArray(SearchBuilder $searchBuilder): array
    {
        $search = $searchBuilder->getSearch();

        return [
            'filters' => \array_map(fn (object $condition): array => $this->serializeCondition($condition), $search->filters),
            'sortBys' => $search->sortBys,
            'limit' => $search->limit,
            'offset' => $search->offset,
            'highlight' => [
                'fields' => $search->highlightFields,
                'preTag' => $search->highlightPreTag,
                'postTag' => $search->highlightPostTag,
            ],
            'distinct' => $search->distinct,
            'facets' => \array_map(fn (AbstractFacet $facet): array => $this->serializeFacet($facet), $search->facets),
        ];
    }

    public function toJson(SearchBuilder $searchBuilder): string
    {
        return \json_encode($this->toArray($searchBuilder), \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deserializeCondition(array $data): SearchCondition|IdentifierCondition|EqualCondition|NotEqualCondition|GreaterThanCondition|GreaterThanEqualCondition|LessThanCondition|LessThanEqualCondition|InCondition|NotInCondition|GeoDistanceCondition|GeoBoundingBoxCondition|AndCondition|OrCondition
    {
        return match (TypeUtil::readStringByKey($data, 'type')) {
            'search' => new SearchCondition(TypeUtil::readStringByKey($data, 'query')),
            'identifier' => new IdentifierCondition(TypeUtil::readStringByKey($data, 'identifier')),
            'equal' => new EqualCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarByKey($data, 'value')),
            'notEqual' => new NotEqualCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarByKey($data, 'value')),
            'greaterThan' => new GreaterThanCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarByKey($data, 'value')),
            'greaterThanEqual' => new GreaterThanEqualCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarByKey($data, 'value')),
            'lessThan' => new LessThanCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarByKey($data, 'value')),
            'lessThanEqual' => new LessThanEqualCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarByKey($data, 'value')),
            'in' => new InCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarListByKey($data, 'values')),
            'notIn' => new NotInCondition(TypeUtil::readStringByKey($data, 'field'), TypeUtil::readScalarListByKey($data, 'values')),
            'geoDistance' => new GeoDistanceCondition(
                TypeUtil::readStringByKey($data, 'field'),
                TypeUtil::readFloatByKey($data, 'latitude'),
                TypeUtil::readFloatByKey($data, 'longitude'),
                TypeUtil::readIntByKey($data, 'distance'),
            ),
            'geoBoundingBox' => new GeoBoundingBoxCondition(
                TypeUtil::readStringByKey($data, 'field'),
                TypeUtil::readFloatByKey($data, 'northLatitude'),
                TypeUtil::readFloatByKey($data, 'eastLongitude'),
                TypeUtil::readFloatByKey($data, 'southLatitude'),
                TypeUtil::readFloatByKey($data, 'westLongitude'),
            ),
            'and' => new AndCondition(...$this->deserializeConditions(TypeUtil::readArrayByKey($data, 'conditions'))),
            'or' => new OrCondition(...$this->deserializeConditions(TypeUtil::readArrayByKey($data, 'conditions'))),
            default => throw new \InvalidArgumentException(\sprintf('Unsupported condition type "%s".', TypeUtil::readStringByKey($data, 'type'))),
        };
    }

    /**
     * @param array<mixed> $conditions
     *
     * @return list<SearchCondition|IdentifierCondition|EqualCondition|NotEqualCondition|GreaterThanCondition|GreaterThanEqualCondition|LessThanCondition|LessThanEqualCondition|InCondition|NotInCondition|GeoDistanceCondition|GeoBoundingBoxCondition|AndCondition|OrCondition>
     */
    private function deserializeConditions(array $conditions): array
    {
        $deserializedConditions = [];
        foreach ($conditions as $condition) {
            $deserializedConditions[] = $this->deserializeCondition(TypeUtil::ensureStringKeyedArrayValue($condition, 'grouped condition'));
        }

        return $deserializedConditions;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deserializeFacet(array $data): AbstractFacet
    {
        return match (TypeUtil::readStringByKey($data, 'type')) {
            'count' => new CountFacet(
                TypeUtil::readStringByKey($data, 'field'),
                TypeUtil::readStringKeyedArrayByKey($data, 'options', []),
            ),
            'minMax' => new MinMaxFacet(
                TypeUtil::readStringByKey($data, 'field'),
                TypeUtil::readStringKeyedArrayByKey($data, 'options', []),
            ),
            default => throw new \InvalidArgumentException(\sprintf('Unsupported facet type "%s".', TypeUtil::readStringByKey($data, 'type'))),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCondition(object $condition): array
    {
        return match (true) {
            $condition instanceof SearchCondition => [
                'type' => 'search',
                'query' => $condition->query,
            ],
            $condition instanceof IdentifierCondition => [
                'type' => 'identifier',
                'identifier' => $condition->identifier,
            ],
            $condition instanceof EqualCondition => $this->serializeFieldValueCondition('equal', $condition->field, $condition->value),
            $condition instanceof NotEqualCondition => $this->serializeFieldValueCondition('notEqual', $condition->field, $condition->value),
            $condition instanceof GreaterThanCondition => $this->serializeFieldValueCondition('greaterThan', $condition->field, $condition->value),
            $condition instanceof GreaterThanEqualCondition => $this->serializeFieldValueCondition('greaterThanEqual', $condition->field, $condition->value),
            $condition instanceof LessThanCondition => $this->serializeFieldValueCondition('lessThan', $condition->field, $condition->value),
            $condition instanceof LessThanEqualCondition => $this->serializeFieldValueCondition('lessThanEqual', $condition->field, $condition->value),
            $condition instanceof InCondition => [
                'type' => 'in',
                'field' => $condition->field,
                'values' => $condition->values,
            ],
            $condition instanceof NotInCondition => [
                'type' => 'notIn',
                'field' => $condition->field,
                'values' => $condition->values,
            ],
            $condition instanceof GeoDistanceCondition => [
                'type' => 'geoDistance',
                'field' => $condition->field,
                'latitude' => $condition->latitude,
                'longitude' => $condition->longitude,
                'distance' => $condition->distance,
            ],
            $condition instanceof GeoBoundingBoxCondition => [
                'type' => 'geoBoundingBox',
                'field' => $condition->field,
                'northLatitude' => $condition->northLatitude,
                'eastLongitude' => $condition->eastLongitude,
                'southLatitude' => $condition->southLatitude,
                'westLongitude' => $condition->westLongitude,
            ],
            $condition instanceof AbstractGroupCondition => [
                'type' => $condition instanceof AndCondition ? 'and' : 'or',
                'conditions' => \array_map(fn (object $groupedCondition): array => $this->serializeCondition($groupedCondition), $condition->conditions),
            ],
            default => throw new \InvalidArgumentException(\sprintf('Unsupported search condition "%s".', $condition::class)),
        };
    }

    /**
     * @return array{type: string, field: string, value: string|int|float|bool}
     */
    private function serializeFieldValueCondition(string $type, string $field, string|int|float|bool $value): array
    {
        return [
            'type' => $type,
            'field' => $field,
            'value' => $value,
        ];
    }

    /**
     * @return array{type: string, field: string, options: array<string, mixed>}
     */
    private function serializeFacet(AbstractFacet $facet): array
    {
        return match (true) {
            $facet instanceof CountFacet => [
                'type' => 'count',
                'field' => $facet->field,
                'options' => $facet->options,
            ],
            $facet instanceof MinMaxFacet => [
                'type' => 'minMax',
                'field' => $facet->field,
                'options' => $facet->options,
            ],
            default => throw new \InvalidArgumentException(\sprintf('Unsupported facet type "%s".', $facet::class)),
        };
    }
}
