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
use CmsIg\Seal\Schema\Schema;

final class PhpFileLoader implements LoaderInterface
{
    /**
     * @param string[] $directories
     */
    public function __construct(
        private readonly array $directories,
        private readonly string $indexNamePrefix = '',
    ) {
    }

    public function load(): Schema
    {
        /** @var Index[] $indexes */
        $indexes = [];

        foreach ($this->directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory),
                \RecursiveIteratorIterator::LEAVES_ONLY,
            );

            $pathIndexes = [];
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo) {
                    continue;
                }

                if ('php' !== $file->getFileInfo()->getExtension()) {
                    continue;
                }

                $index = require $file->getRealPath();

                if (!$index instanceof Index) {
                    throw new \RuntimeException(\sprintf('File "%s" must return an instance of "%s".', $file->getRealPath(), Index::class));
                }

                $pathIndexes[$file->getRealPath()] = $index;
            }

            \ksort($pathIndexes); // make sure to import the files on all system in the same order

            foreach ($pathIndexes as $index) {
                $name = $index->name;
                if (isset($indexes[$name])) {
                    $index = new Index($this->indexNamePrefix . $name, $this->mergeFields($indexes[$index->name]->fields, $index->fields));
                } else {
                    $index = new Index($this->indexNamePrefix . $name, $index->fields);
                }

                $indexes[$name] = $index;
            }
        }

        return new Schema($indexes);
    }

    /**
     * @param Field\AbstractField[] $fields
     * @param Field\AbstractField[] $newFields
     *
     * @return Field\AbstractField[]
     */
    private function mergeFields(array $fields, array $newFields): array
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
     * @template T of Field\AbstractField
     *
     * @param T $field
     * @param T $newField
     *
     * @return T
     */
    private function mergeField(Field\AbstractField $field, Field\AbstractField $newField): Field\AbstractField
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
                options: \array_replace_recursive($field->options, $newField->options),
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
                options: \array_replace_recursive($field->options, $newField->options),
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
                options: \array_replace_recursive($field->options, $newField->options),
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
                options: \array_replace_recursive($field->options, $newField->options),
            );
        }

        if ($field instanceof Field\ObjectField && $newField instanceof Field\ObjectField) {
            // @phpstan-ignore-next-line
            return new Field\ObjectField(
                $newField->name,
                fields: $this->mergeFields($field->fields, $newField->fields),
                multiple: $newField->multiple,
                options: \array_replace_recursive($field->options, $newField->options),
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
                options: \array_replace_recursive($field->options, $newField->options),
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
