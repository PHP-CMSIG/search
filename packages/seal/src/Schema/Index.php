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

namespace CmsIg\Seal\Schema;

use CmsIg\Seal\Schema\Exception\FieldByPathNotFoundException;
use CmsIg\Seal\Schema\Field\AbstractField;
use CmsIg\Seal\Schema\Field\GeoPointField;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\ObjectField;
use CmsIg\Seal\Schema\Field\TypedField;

/**
 * @readonly
 */
final class Index
{
    private readonly IdentifierField|null $identifierField;

    /**
     * @var string[]
     */
    public readonly array $searchableFields;

    /**
     * @var string[]
     */
    public readonly array $sortableFields;

    /**
     * @var string[]
     */
    public readonly array $filterableFields;

    /**
     * @param array<string, AbstractField> $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly array $fields,
    ) {
        $attributes = $this->getAttributes($fields);
        $this->searchableFields = $attributes['searchableFields'];
        $this->filterableFields = $attributes['filterableFields'];
        $this->sortableFields = $attributes['sortableFields'];
        $this->identifierField = $attributes['identifierField'];
    }

    public function getIdentifierField(): IdentifierField
    {
        if (!$this->identifierField instanceof Field\IdentifierField) { // validating the identifierField here as merged Index configuration could be have no identifier
            throw new \LogicException(
                'No "IdentifierField" found for index "' . $this->name . '" but is required.',
            );
        }

        return $this->identifierField;
    }

    public function getGeoPointField(): GeoPointField|null
    {
        foreach ($this->fields as $field) {
            if ($field instanceof GeoPointField) {
                return $field;
            }
        }

        return null;
    }

    public function getFieldByPath(string $path): AbstractField
    {
        $pathParts = \explode('.', $path);
        $fields = $this->fields;

        while (true) {
            $field = $fields[\current($pathParts)] ?? null;

            if ($field instanceof TypedField) {
                $fields = $field->types[\current($pathParts)];
            } elseif ($field instanceof ObjectField) {
                $fields = $field->fields;
            } elseif ($field instanceof AbstractField) {
                return $field;
            } else {
                throw new FieldByPathNotFoundException($this->name, $path);
            }
        }
    }

    /**
     * @param Field\AbstractField[] $fields
     *
     * @return ($withoutIdentifierField is false ? array{
     *     searchableFields: string[],
     *     filterableFields: string[],
     *     sortableFields: string[],
     *     identifierField: IdentifierField|null,
     * } : array{
     *     searchableFields: string[],
     *     filterableFields: string[],
     *     sortableFields: string[],
     * })
     */
    private function getAttributes(array $fields, bool $withoutIdentifierField = false): array
    {
        $identifierField = null;

        $attributes = [
            'searchableFields' => [],
            'filterableFields' => [],
            'sortableFields' => [],
        ];

        foreach ($fields as $name => $field) {
            \assert(
                (string) $name === $field->name, // this may change in future, see https://github.com/php-cmsig/search/issues/200
                \sprintf(
                    'A field named "%s" does not match key "%s" in index "%s", this is at current state required and may change in future.',
                    $field->name,
                    $name,
                    $this->name,
                ),
            );

            \assert(
                1 === \preg_match('/^([a-z]|[A-Z])\w+$/', $field->name),
                \sprintf(
                    'A field named "%s" uses unsupported character in index "%s", supported characters are "a-z", "A-Z", "0-9" and "_".',
                    $field->name,
                    $this->name,
                ),
            );

            if ($field instanceof Field\ObjectField) {
                foreach ($this->getAttributes($field->fields, true) as $attributeType => $fieldNames) {
                    foreach ($fieldNames as $fieldName) {
                        $attributes[$attributeType][] = $name . '.' . $fieldName;
                    }
                }

                continue;
            } elseif ($field instanceof Field\TypedField) {
                foreach ($field->types as $type => $fields) {
                    foreach ($this->getAttributes($fields, true) as $attributeType => $fieldNames) {
                        foreach ($fieldNames as $fieldName) {
                            $attributes[$attributeType][] = $name . '.' . $type . '.' . $fieldName;
                        }
                    }
                }

                continue;
            }

            if ($field->searchable) {
                $attributes['searchableFields'][] = $name;
            }

            if ($field->filterable) {
                $attributes['filterableFields'][] = $name;
            }

            if ($field->sortable) {
                $attributes['sortableFields'][] = $name;
            }

            if ($field instanceof IdentifierField) {
                $identifierField = $field;
            }
        }

        if ($withoutIdentifierField) {
            return $attributes;
        }

        $attributes['identifierField'] = $identifierField;

        return $attributes;
    }
}
