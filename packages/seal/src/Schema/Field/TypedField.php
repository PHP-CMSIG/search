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
final class TypedField extends AbstractField
{
    /**
     * @param array<string, array<string, AbstractField>> $types
     * @param array<string, mixed> $options
     *
     * @readonly
     */
    public function __construct(
        string $name,
        public readonly string $typeField,
        public readonly array $types,
        bool $multiple = false,
        array $options = [],
    ) {
        $searchable = false;
        $filterable = false;
        $sortable = false;

        foreach ($types as $fields) {
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
