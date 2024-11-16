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
 * Type to store any text, options can maybe use to specify it more specific.
 */
final class TextField extends AbstractField
{
    /**
     * @param array<string, mixed> $options
     *
     * @readonly
     */
    public function __construct(
        string $name,
        bool $multiple = false,
        bool $searchable = true,
        bool $filterable = false,
        bool $sortable = false,
        array $options = [],
    ) {
        parent::__construct(
            $name,
            $multiple,
            $searchable,
            $filterable,
            $sortable,
            $options,
        );
    }
}
