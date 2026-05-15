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

namespace CmsIg\Seal\Odm\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Field
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly string|null $name = null,
        public readonly bool|null $searchable = null,
        public readonly bool $filterable = false,
        public readonly bool $sortable = false,
        public readonly bool $distinct = false,
        public readonly bool $facet = false,
        public readonly array $options = [],
    ) {
    }
}
