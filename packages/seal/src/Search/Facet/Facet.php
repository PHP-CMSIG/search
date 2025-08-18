<?php

namespace CmsIg\Seal\Search\Facet;

/**
 * Simpler access to the different facet classes.
 */
final class Facet
{
    /**
     * @param array<string, mixed> $options
     */
    public static function count(string $field, array $options = []): CountFacet
    {
        return new CountFacet($field, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function minMax(string $field, array $options = []): MinMaxFacet
    {
        return new MinMaxFacet($field, $options);
    }
}
