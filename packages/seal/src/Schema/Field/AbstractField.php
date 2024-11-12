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
 * @readonly
 */
abstract class AbstractField
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $multiple,
        public readonly bool $searchable,
        public readonly bool $filterable,
        public readonly bool $sortable,
        public readonly array $options,
    ) {
    }
}
