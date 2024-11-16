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
 * Type to store fields inside a nested object.
 *
 * @readonly
 */
final class ObjectField extends AbstractField
{
    /**
     * @param array<string, AbstractField> $fields
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $name,
        readonly public array $fields,
        bool $multiple = false,
        array $options = [],
    ) {
        $searchable = false;
        $filterable = false;
        $sortable = false;

        foreach ($fields as $field) {
            if ($field->searchable) {
                $searchable = true;
            }

            if ($field->filterable) {
                $filterable = true;
            }

            if ($field->sortable) {
                $sortable = true;
            }
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
