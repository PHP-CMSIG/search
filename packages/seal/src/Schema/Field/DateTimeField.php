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
 * Type to store date and date times.
 *
 * @property false $searchable
 *
 * @readonly
 */
final class DateTimeField extends AbstractField
{
    /**
     * @param false $searchable
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $name,
        bool $multiple = false,
        bool $searchable = false,
        bool $filterable = false,
        bool $sortable = false,
        array $options = [],
    ) {
        if ($searchable) { // @phpstan-ignore-line
            throw new \InvalidArgumentException('Searchability for DateTimeField is not yet implemented: https://github.com/php-cmsig/search/issues/97');
        }

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
