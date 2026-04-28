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

use CmsIg\Seal\Search\TypeUtil;

final class FormSearchInputNormalizer
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $normalized = $data;

        self::normalizeLimitAndOffset($normalized);
        self::normalizeFilters($normalized);

        return $normalized;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function normalizeLimitAndOffset(array &$data): void
    {
        if (\array_key_exists('offset', $data)) {
            $data['offset'] = TypeUtil::toIntValue($data['offset'], 'offset');
        }

        if (\array_key_exists('limit', $data)) {
            if (null === $data['limit'] || '' === $data['limit']) {
                $data['limit'] = null;

                return;
            }

            $data['limit'] = TypeUtil::toIntValue($data['limit'], 'limit');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function normalizeFilters(array &$data): void
    {
        $filters = TypeUtil::readArrayByKey($data, 'filters', []);
        foreach ($filters as $index => $filter) {
            $context = \sprintf('filters[%s]', (string) $index);
            $data['filters'][$index] = self::normalizeCondition(TypeUtil::ensureStringKeyedArrayValue($filter, $context), $context);
        }
    }

    /**
     * @param array<mixed> $condition
     *
     * @return array<mixed>
     */
    private static function normalizeCondition(array $condition, string $context): array
    {
        $type = TypeUtil::readNullableStringByKey($condition, 'type');
        if (null === $type) {
            return $condition;
        }

        if ('geoDistance' === $type) {
            if (\array_key_exists('latitude', $condition)) {
                $condition['latitude'] = TypeUtil::toFloatValue($condition['latitude'], $context . '.latitude');
            }

            if (\array_key_exists('longitude', $condition)) {
                $condition['longitude'] = TypeUtil::toFloatValue($condition['longitude'], $context . '.longitude');
            }

            if (\array_key_exists('distance', $condition)) {
                $condition['distance'] = TypeUtil::toIntValue($condition['distance'], $context . '.distance');
            }
        }

        if ('geoBoundingBox' === $type) {
            if (\array_key_exists('northLatitude', $condition)) {
                $condition['northLatitude'] = TypeUtil::toFloatValue($condition['northLatitude'], $context . '.northLatitude');
            }

            if (\array_key_exists('eastLongitude', $condition)) {
                $condition['eastLongitude'] = TypeUtil::toFloatValue($condition['eastLongitude'], $context . '.eastLongitude');
            }

            if (\array_key_exists('southLatitude', $condition)) {
                $condition['southLatitude'] = TypeUtil::toFloatValue($condition['southLatitude'], $context . '.southLatitude');
            }

            if (\array_key_exists('westLongitude', $condition)) {
                $condition['westLongitude'] = TypeUtil::toFloatValue($condition['westLongitude'], $context . '.westLongitude');
            }
        }

        if ('and' === $type || 'or' === $type) {
            foreach (TypeUtil::readArrayByKey($condition, 'conditions', []) as $groupedIndex => $groupedCondition) {
                $condition['conditions'][$groupedIndex] = self::normalizeCondition(
                    TypeUtil::ensureStringKeyedArrayValue($groupedCondition, \sprintf('%s.conditions[%s]', $context, (string) $groupedIndex)),
                    \sprintf('%s.conditions[%s]', $context, (string) $groupedIndex),
                );
            }
        }

        return $condition;
    }
}
