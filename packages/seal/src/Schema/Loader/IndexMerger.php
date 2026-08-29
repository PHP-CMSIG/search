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

namespace CmsIg\Seal\Schema\Loader;

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;

/**
 * @internal this class is only intended to be used internally by schema loaders
 */
final class IndexMerger
{
    /**
     * @param array<string, Index> $indexes
     *
     * @return array<string, Index>
     */
    public function mergeIndexes(array $indexes, Index $index, string $indexNamePrefix = ''): array
    {
        $name = $index->name;
        if (isset($indexes[$name])) {
            $index = new Index(
                $indexNamePrefix . $name,
                $this->mergeFields($indexes[$name]->fields, $index->fields),
                $this->mergeOptions($indexes[$name]->options, $index->options),
            );
        } else {
            $index = new Index($indexNamePrefix . $name, $index->fields, $index->options);
        }

        $indexes[$name] = $index;

        return $indexes;
    }

    /**
     * @param array<string, Field\AbstractField> $fields
     * @param array<string, Field\AbstractField> $newFields
     *
     * @return array<string, Field\AbstractField>
     */
    public function mergeFields(array $fields, array $newFields): array
    {
        foreach ($newFields as $name => $newField) {
            if (isset($fields[$name])) {
                if ($fields[$name]::class !== $newField::class) {
                    throw new \RuntimeException(\sprintf('Field "%s" must be of type "%s" but "%s" given.', $name, $fields[$name]::class, $newField::class));
                }

                $newField = $this->mergeField($fields[$name], $newField);
            }

            $fields[$newField->name] = $newField;
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $newOptions
     *
     * @return array<string, mixed>
     */
    public function mergeOptions(array $options, array $newOptions): array
    {
        return \array_replace_recursive($options, $newOptions); // @phpstan-ignore-line return.type
    }

    /**
     * @template T of Field\AbstractField
     *
     * @param T $field
     * @param T $newField
     *
     * @return T
     */
    public function mergeField(Field\AbstractField $field, Field\AbstractField $newField): Field\AbstractField
    {
        if ($newField instanceof Field\IdentifierField) {
            return $newField;
        }

        if ($field instanceof Field\TextField && $newField instanceof Field\TextField) {
            // @phpstan-ignore-next-line
            return new Field\TextField(
                $newField->name,
                multiple: $newField->multiple,
                searchable: $newField->searchable,
                filterable: $newField->filterable,
                sortable: $newField->sortable,
                distinct: $newField->distinct,
                facet: $newField->facet,
                options: $this->mergeOptions($field->options, $newField->options),
            );
        }

        if ($field instanceof Field\IntegerField && $newField instanceof Field\IntegerField) {
            // @phpstan-ignore-next-line
            return new Field\IntegerField(
                $newField->name,
                multiple: $newField->multiple,
                searchable: $newField->searchable,
                filterable: $newField->filterable,
                sortable: $newField->sortable,
                distinct: $newField->distinct,
                facet: $newField->facet,
                options: $this->mergeOptions($field->options, $newField->options),
            );
        }

        if ($field instanceof Field\FloatField && $newField instanceof Field\FloatField) {
            // @phpstan-ignore-next-line
            return new Field\FloatField(
                $newField->name,
                multiple: $newField->multiple,
                searchable: $newField->searchable,
                filterable: $newField->filterable,
                sortable: $newField->sortable,
                distinct: $newField->distinct,
                facet: $newField->facet,
                options: $this->mergeOptions($field->options, $newField->options),
            );
        }

        if ($field instanceof Field\BooleanField && $newField instanceof Field\BooleanField) {
            // @phpstan-ignore-next-line
            return new Field\BooleanField(
                $newField->name,
                multiple: $newField->multiple,
                searchable: $newField->searchable,
                filterable: $newField->filterable,
                sortable: $newField->sortable,
                distinct: $newField->distinct,
                facet: $newField->facet,
                options: $this->mergeOptions($field->options, $newField->options),
            );
        }

        if ($field instanceof Field\DateTimeField && $newField instanceof Field\DateTimeField) {
            // @phpstan-ignore-next-line
            return new Field\DateTimeField(
                $newField->name,
                multiple: $newField->multiple,
                searchable: $newField->searchable,
                filterable: $newField->filterable,
                sortable: $newField->sortable,
                distinct: $newField->distinct,
                facet: $newField->facet,
                options: $this->mergeOptions($field->options, $newField->options),
            );
        }

        if ($field instanceof Field\ObjectField && $newField instanceof Field\ObjectField) {
            // @phpstan-ignore-next-line
            return new Field\ObjectField(
                $newField->name,
                fields: $this->mergeFields($field->fields, $newField->fields),
                multiple: $newField->multiple,
                options: $this->mergeOptions($field->options, $newField->options),
            );
        }

        if ($field instanceof Field\TypedField && $newField instanceof Field\TypedField) {
            $types = $field->types;
            foreach ($newField->types as $name => $newTypedFields) {
                if (isset($types[$name])) {
                    $types[$name] = $this->mergeFields($types[$name], $newTypedFields);

                    continue;
                }

                $types[$name] = $newTypedFields;
            }

            // @phpstan-ignore-next-line
            return new Field\TypedField(
                $newField->name,
                typeField: $newField->typeField,
                types: $types,
                multiple: $newField->multiple,
                options: $this->mergeOptions($field->options, $newField->options),
            );
        }

        throw new \RuntimeException(\sprintf(
            'Field "%s" must be of type "%s" but "%s" and "%s" given.',
            $field->name,
            Field\AbstractField::class,
            $field::class,
            $newField::class,
        ));
    }
}
