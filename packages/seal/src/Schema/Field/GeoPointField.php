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

namespace CmsIg\Seal\Schema\Field;

/**
 * Type to store geopoint with latitude and longitude.
 *
 * latitude: -90 to 90
 * longitude: -180 to 180
 *
 * ATTENTION: Different search engines support only one field for geopoint per index.
 *
 * @property false $multiple
 * @property false $searchable
 * @property false $facet
 *
 * @readonly
 */
final class GeoPointField extends AbstractField
{
    /**
     * @param false $searchable
     * @param false $multiple
     * @param false $facet
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $name,
        bool $multiple = false,
        bool $searchable = false,
        bool $filterable = false,
        bool $sortable = false,
        bool $distinct = false,
        bool $facet = false,
        array $options = [],
    ) {
        if ($searchable) { // @phpstan-ignore-line
            throw new \InvalidArgumentException('Searchability for GeoPointField is not yet implemented: https://github.com/php-cmsig/search/issues/97');
        }

        if ($facet) { // @phpstan-ignore-line
            throw new \InvalidArgumentException('Facet for GeoPointField is not yet implemented: <TODO create issue>');
        }

        parent::__construct(
            $name,
            $multiple,
            $searchable,
            $filterable,
            $sortable,
            $distinct,
            $facet,
            $options,
        );
    }
}
