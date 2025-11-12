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
 * @experimental This is an experimental feature and the API can change at any time.
 *               If you use it let us know and give feedback here: https://github.com/PHP-CMSIG/search/issues/614
 *
 * @property false $multiple
 * @property false $searchable
 * @property false $filterable
 * @property false $sortable
 * @property false $facet
 * @property false $distinct
 *
 * @readonly
 *
 * Type to store unstructured JSON objects (array<string, mixed) for example for some metadata.
 */
final class JsonObjectField extends AbstractField
{
    /**
     * @param false $multiple
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $name,
        bool $multiple = false,
        array $options = [],
    ) {
        parent::__construct(
            $name,
            $multiple,
            searchable: false,
            filterable: false,
            sortable: false,
            distinct: false,
            facet: false,
            options: $options,
        );
    }
}
