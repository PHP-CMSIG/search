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

use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition\AbstractGroupCondition;
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
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Facet\AbstractFacet;

final class SearchBuilderFactoryConfig
{
    /**
     * @param array<string> $filterFields
     * @param array<string> $sortFields
     * @param array<string> $facetFields
     * @param array<string> $distinctFields
     * @param array<string> $highlightFields
     */
    public function __construct(
        public readonly array $filterFields = [],
        public readonly array $sortFields = [],
        public readonly array $facetFields = [],
        public readonly array $distinctFields = [],
        public readonly array $highlightFields = [],
        public readonly bool $allowSearch = false,
        public readonly bool $allowIdentifier = false,
        public readonly int|null $maxLimit = 0,
    ) {
    }

    public function validateCondition(object $condition, Index $index): void
    {
        if ($condition instanceof SearchCondition) {
            if (false === $this->allowSearch) {
                throw new \InvalidArgumentException('Search conditions are not allowed by this search builder factory config.');
            }

            return;
        }

        if ($condition instanceof IdentifierCondition) {
            if (false === $this->allowIdentifier) {
                throw new \InvalidArgumentException('Identifier conditions are not allowed by this search builder factory config.');
            }

            return;
        }

        if ($condition instanceof AbstractGroupCondition) {
            foreach ($condition->conditions as $groupedCondition) {
                $this->validateCondition($groupedCondition, $index);
            }

            return;
        }

        if (
            $condition instanceof EqualCondition
            || $condition instanceof NotEqualCondition
            || $condition instanceof GreaterThanCondition
            || $condition instanceof GreaterThanEqualCondition
            || $condition instanceof LessThanCondition
            || $condition instanceof LessThanEqualCondition
            || $condition instanceof InCondition
            || $condition instanceof NotInCondition
            || $condition instanceof GeoDistanceCondition
            || $condition instanceof GeoBoundingBoxCondition
        ) {
            $this->assertSchemaField('filterable', $condition->field, $index->filterableFields, $index);
            $this->assertAllowed('filter', $condition->field, $this->filterFields);

            return;
        }

        throw new \InvalidArgumentException(\sprintf('Unsupported search condition "%s".', $condition::class));
    }

    public function validateSort(string $field, Index $index): void
    {
        $this->assertSchemaField('sortable', $field, $index->sortableFields, $index);
        $this->assertAllowed('sort', $field, $this->sortFields);
    }

    public function validateLimit(int|null $limit): void
    {
        if (null !== $limit && null !== $this->maxLimit && $limit > $this->maxLimit) {
            throw new \InvalidArgumentException(\sprintf('Search builder limit "%d" exceeds the allowed maximum of "%d".', $limit, $this->maxLimit));
        }
    }

    public function validateHighlight(string $field, Index $index): void
    {
        $this->assertSchemaField('searchable', $field, $index->searchableFields, $index);
        $this->assertAllowed('highlight', $field, $this->highlightFields);
    }

    public function validateDistinct(string $field, Index $index): void
    {
        $this->assertSchemaField('distinct', $field, $index->distinctFields, $index);
        $this->assertAllowed('distinct', $field, $this->distinctFields);
    }

    public function validateFacet(AbstractFacet $facet, Index $index): void
    {
        $this->assertSchemaField('facet', $facet->field, $index->facetFields, $index);
        $this->assertAllowed('facet', $facet->field, $this->facetFields);
    }

    /**
     * @param array<string> $allowed
     */
    private function assertAllowed(string $context, string $value, array $allowed): void
    {
        if (!\in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException(\sprintf('Search builder %s "%s" is not allowed by this config.', $context, $value));
        }
    }

    /**
     * @param array<string> $allowedFields
     */
    private function assertSchemaField(string $context, string $field, array $allowedFields, Index $index): void
    {
        if (!\in_array($field, $allowedFields, true)) {
            throw new \InvalidArgumentException(\sprintf('Search builder %s field "%s" is not configured as %s in index "%s".', $context, $field, $context, $index->name));
        }
    }
}
