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

namespace CmsIg\Seal\Search\Condition;

/**
 * Simpler access to the different condition classes.
 */
final class Condition
{
    private function __construct()
    {
    }

    public static function search(string $query): SearchCondition
    {
        return new SearchCondition($query);
    }

    public static function identifier(string $id): IdentifierCondition
    {
        return new IdentifierCondition($id);
    }

    public static function equal(string $field, string|int|float|bool $value): EqualCondition
    {
        return new EqualCondition($field, $value);
    }

    public static function notEqual(string $field, string|int|float|bool $value): NotEqualCondition
    {
        return new NotEqualCondition($field, $value);
    }

    public static function greaterThan(string $field, string|int|float|bool $value): GreaterThanCondition
    {
        return new GreaterThanCondition($field, $value);
    }

    public static function greaterThanEqual(string $field, string|int|float|bool $value): GreaterThanEqualCondition
    {
        return new GreaterThanEqualCondition($field, $value);
    }

    public static function lessThan(string $field, string|int|float|bool $value): LessThanCondition
    {
        return new LessThanCondition($field, $value);
    }

    public static function lessThanEqual(string $field, string|int|float|bool $value): LessThanEqualCondition
    {
        return new LessThanEqualCondition($field, $value);
    }

    /**
     * @param list<string|int|float|bool> $values
     */
    public static function in(string $field, array $values): InCondition
    {
        return new InCondition($field, $values);
    }

    /**
     * @param list<string|int|float|bool> $values
     */
    public static function notIn(string $field, array $values): NotInCondition
    {
        return new NotInCondition($field, $values);
    }

    /**
     * The order may first be unusually, but it is the same as in common JS libraries like.
     *
     * @see https://docs.mapbox.com/help/glossary/bounding-box/
     * @see https://developers.google.com/maps/documentation/javascript/reference/coordinates#LatLngBounds
     */
    public static function geoBoundingBox(
        string $field,
        float $northLatitude, // top
        float $eastLongitude, // right
        float $southLatitude, // bottom
        float $westLongitude, // left
    ): GeoBoundingBoxCondition {
        return new GeoBoundingBoxCondition($field, $northLatitude, $eastLongitude, $southLatitude, $westLongitude);
    }

    /**
     * @param int $distance search radius in meters
     */
    public static function geoDistance(
        string $field,
        float $latitude,
        float $longitude,
        int $distance,
    ): GeoDistanceCondition {
        return new GeoDistanceCondition($field, $latitude, $longitude, $distance);
    }

    /**
     * @param EqualCondition|GreaterThanCondition|GreaterThanEqualCondition|IdentifierCondition|InCondition|LessThanCondition|LessThanEqualCondition|NotEqualCondition|NotInCondition|AndCondition|OrCondition $conditions
     */
    public static function and(...$conditions): AndCondition
    {
        return new AndCondition(...$conditions);
    }

    /**
     * @param EqualCondition|GreaterThanCondition|GreaterThanEqualCondition|IdentifierCondition|InCondition|LessThanCondition|LessThanEqualCondition|NotEqualCondition|NotInCondition|AndCondition|OrCondition $conditions
     */
    public static function or(...$conditions): OrCondition
    {
        return new OrCondition(...$conditions);
    }
}
