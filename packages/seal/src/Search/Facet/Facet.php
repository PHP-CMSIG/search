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

namespace CmsIg\Seal\Search\Facet;

/**
 * Simpler access to the different facet classes.
 */
final class Facet
{
    private function __construct()
    {
    }

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
