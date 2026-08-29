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

namespace CmsIg\Seal\Odm\Mapper;

use CmsIg\Seal\Odm\Schema\Loader\AttributeLoader;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;

final class OdmDataMapper implements OdmDataMapperInterface
{
    /**
     * @var array<class-string, \ReflectionClass<object>>
     */
    private array $reflectionClasses = [];

    /**
     * @var array<string, \ReflectionProperty>
     */
    private array $reflectionProperties = [];

    public function __construct(
        private readonly Schema $schema,
    ) {
    }

    public function objectToArray(string $index, object $object): array
    {
        $schemaIndex = $this->getSchemaIndex($index);
        $odmMetadata = $this->getIndexMetadata($index, $schemaIndex);

        if (!$object instanceof $odmMetadata['class']) {
            throw new \RuntimeException(\sprintf(
                'Object for index "%s" must be an instance of "%s", got "%s".',
                $index,
                $odmMetadata['class'],
                $object::class,
            ));
        }

        return $this->extractObject($index, $object, $schemaIndex->fields);
    }

    public function arrayToObject(string $index, array $document): object
    {
        $schemaIndex = $this->getSchemaIndex($index);
        $odmMetadata = $this->getIndexMetadata($index, $schemaIndex);

        return $this->hydrateObject(
            $index,
            $odmMetadata['class'],
            $document,
            $schemaIndex->fields,
        );
    }

    /**
     * @param array<string, Field\AbstractField> $fields
     *
     * @return array<string, mixed>
     */
    private function extractObject(string $index, object $object, array $fields): array
    {
        $document = [];

        foreach ($fields as $fieldName => $field) {
            $property = $this->getReflectionProperty($object::class, $fieldName);

            if (!$property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);

            if (null === $value) {
                if ($field instanceof Field\ObjectField || $field->multiple) {
                    continue;
                }

                $document[$fieldName] = null;

                continue;
            }

            $document[$fieldName] = match (true) {
                $field instanceof Field\ObjectField => $this->extractObjectField($index, $fieldName, $value, $field),
                $field instanceof Field\DateTimeField => $this->extractDateTimeField($index, $fieldName, $value, $field),
                default => $this->extractScalarField($index, $fieldName, $value, $field),
            };
        }

        return $document;
    }

    /**
     * @param array<string, mixed> $document
     * @param array<string, Field\AbstractField> $fields
     * @param class-string $className
     */
    private function hydrateObject(
        string $index,
        string $className,
        array $document,
        array $fields,
    ): object {
        $object = $this->getReflectionClass($className)->newInstanceWithoutConstructor();

        foreach ($fields as $fieldName => $field) {
            if (!\array_key_exists($fieldName, $document)) {
                continue;
            }

            $property = $this->getReflectionProperty($className, $fieldName);
            $value = $document[$fieldName];

            $property->setValue($object, match (true) {
                $field instanceof Field\ObjectField => $this->hydrateObjectField($index, $fieldName, $value, $field),
                $field instanceof Field\DateTimeField => $this->hydrateDateTimeField($index, $fieldName, $value, $field),
                default => $this->hydrateScalarField($index, $fieldName, $value, $field),
            });
        }

        return $object;
    }

    /**
     * @return array{
     *     class: class-string,
     * }
     */
    private function getIndexMetadata(string $index, Index $schemaIndex): array
    {
        $metadata = $schemaIndex->options['odm'] ?? null;
        if (
            !\is_array($metadata)
            || !isset($metadata['class'])
            || !\is_string($metadata['class'])
        ) {
            throw new \RuntimeException(\sprintf(
                'Index "%s" is missing ODM metadata. Build the schema with "%s".',
                $index,
                AttributeLoader::class,
            ));
        }

        return [
            'class' => $this->normalizeClassName($metadata['class'], $index, $index),
        ];
    }

    private function getSchemaIndex(string $index): Index
    {
        if (!isset($this->schema->indexes[$index])) {
            throw new \RuntimeException(\sprintf('Index "%s" was not found in the schema.', $index));
        }

        return $this->schema->indexes[$index];
    }

    /**
     * @return array<string, mixed>|array<array<string, mixed>>
     */
    private function extractObjectField(
        string $index,
        string $fieldName,
        mixed $value,
        Field\ObjectField $field,
    ): array {
        if (!$field->multiple) {
            if (!\is_object($value)) {
                throw new \RuntimeException(\sprintf(
                    'Object field "%s" on index "%s" expects an object value.',
                    $fieldName,
                    $index,
                ));
            }

            return $this->extractObject($index, $value, $field->fields);
        }

        if (!\is_array($value)) {
            throw new \RuntimeException(\sprintf(
                'Object field "%s" on index "%s" expects an array of objects.',
                $fieldName,
                $index,
            ));
        }

        $documents = [];
        foreach ($value as $item) {
            if (!\is_object($item)) {
                throw new \RuntimeException(\sprintf(
                    'Object field "%s" on index "%s" expects an array of objects.',
                    $fieldName,
                    $index,
                ));
            }

            $documents[] = $this->extractObject($index, $item, $field->fields);
        }

        return $documents;
    }

    /**
     * @return object|array<object>|null
     */
    private function hydrateObjectField(
        string $index,
        string $fieldName,
        mixed $value,
        Field\ObjectField $field,
    ): object|array|null {
        if (null === $value) {
            return null;
        }

        $nestedClass = $this->getNestedClass($index, $fieldName, $field);

        if (!$field->multiple) {
            return $this->hydrateObject(
                $index,
                $nestedClass,
                $this->normalizeDocumentArray($value, $fieldName, $index),
                $field->fields,
            );
        }

        if (!\is_array($value)) {
            throw new \RuntimeException(\sprintf(
                'Object field "%s" on index "%s" expects an array of documents.',
                $fieldName,
                $index,
            ));
        }

        $objects = [];
        foreach ($value as $item) {
            $objects[] = $this->hydrateObject(
                $index,
                $nestedClass,
                $this->normalizeDocumentArray($item, $fieldName, $index),
                $field->fields,
            );
        }

        return $objects;
    }

    /**
     * @return string|array<string>
     */
    private function extractDateTimeField(string $index, string $fieldName, mixed $value, Field\DateTimeField $field): array|string
    {
        if (!$field->multiple) {
            if (!$value instanceof \DateTimeInterface) {
                throw new \RuntimeException(\sprintf(
                    'DateTime field "%s" on index "%s" expects a "%s" instance.',
                    $fieldName,
                    $index,
                    \DateTimeInterface::class,
                ));
            }

            return $value->format('Y-m-d H:i:s');
        }

        if (!\is_array($value)) {
            throw new \RuntimeException(\sprintf(
                'DateTime field "%s" on index "%s" expects an array of "%s" instances.',
                $fieldName,
                $index,
                \DateTimeInterface::class,
            ));
        }

        $values = [];
        foreach ($value as $key => $item) {
            if (!$item instanceof \DateTimeInterface) {
                throw new \RuntimeException(\sprintf(
                    'DateTime field "%s" on index "%s" expects an array of "%s" instances.',
                    $fieldName,
                    $index,
                    \DateTimeInterface::class,
                ));
            }

            $values[$key] = $item->format('Y-m-d H:i:s');
        }

        return $values;
    }

    /**
     * @return \DateTimeInterface|\DateTimeInterface[]|null
     */
    private function hydrateDateTimeField(
        string $index,
        string $fieldName,
        mixed $value,
        Field\DateTimeField $field,
    ): \DateTimeInterface|array|null {
        if (null === $value) {
            return null;
        }

        if (!$field->multiple) {
            return $this->hydrateDateTimeValue($index, $fieldName, $value);
        }

        if (!\is_array($value)) {
            throw new \RuntimeException(\sprintf(
                'DateTime field "%s" on index "%s" expects an array of date values.',
                $fieldName,
                $index,
            ));
        }

        $values = [];
        foreach ($value as $key => $item) {
            $values[$key] = $this->hydrateDateTimeValue($index, $fieldName, $item);
        }

        return $values;
    }

    private function hydrateDateTimeValue(string $index, string $fieldName, mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return new \DateTimeImmutable($value->format('c'));
        }

        if (!\is_string($value)) {
            throw new \RuntimeException(\sprintf(
                'DateTime field "%s" on index "%s" expects a string or "%s" instance.',
                $fieldName,
                $index,
                \DateTimeInterface::class,
            ));
        }

        return new \DateTimeImmutable($value);
    }

    private function extractScalarField(string $index, string $fieldName, mixed $value, Field\AbstractField $field): mixed
    {
        if ($field->multiple && !\is_array($value)) {
            throw new \RuntimeException(\sprintf(
                'Field "%s" on index "%s" expects an array value.',
                $fieldName,
                $index,
            ));
        }

        return $value;
    }

    private function hydrateScalarField(string $index, string $fieldName, mixed $value, Field\AbstractField $field): mixed
    {
        if (null !== $value && $field->multiple && !\is_array($value)) {
            throw new \RuntimeException(\sprintf(
                'Field "%s" on index "%s" expects an array document value.',
                $fieldName,
                $index,
            ));
        }

        return $value;
    }

    /**
     * @param class-string $className
     *
     * @return \ReflectionClass<object>
     */
    private function getReflectionClass(string $className): \ReflectionClass
    {
        if (!isset($this->reflectionClasses[$className])) {
            $this->reflectionClasses[$className] = new \ReflectionClass($className);
        }

        return $this->reflectionClasses[$className];
    }

    /**
     * @param class-string $className
     */
    private function getReflectionProperty(string $className, string $propertyName): \ReflectionProperty
    {
        $cacheKey = $className . '::' . $propertyName;
        if (isset($this->reflectionProperties[$cacheKey])) {
            return $this->reflectionProperties[$cacheKey];
        }

        try {
            $property = $this->getReflectionClass($className)->getProperty($propertyName);
        } catch (\ReflectionException $exception) {
            throw new \RuntimeException(\sprintf(
                'Property "%s::$%s" mapped by field name does not exist.',
                $className,
                $propertyName,
            ), $exception->getCode(), previous: $exception);
        }

        return $this->reflectionProperties[$cacheKey] = $property;
    }

    /**
     * @return class-string
     */
    private function getNestedClass(string $index, string $fieldName, Field\ObjectField $field): string
    {
        $metadata = $field->options['odm'] ?? null;
        if (!\is_array($metadata) || !isset($metadata['class'])) {
            throw new \RuntimeException(\sprintf(
                'Object field "%s" on index "%s" is missing nested ODM metadata.',
                $fieldName,
                $index,
            ));
        }

        return $this->normalizeClassName($metadata['class'], $fieldName, $index);
    }

    /**
     * @return class-string
     */
    private function normalizeClassName(mixed $className, string $fieldName, string $index): string
    {
        if (!\is_string($className) || !\class_exists($className)) {
            throw new \RuntimeException(\sprintf(
                'Field "%s" on index "%s" contains invalid ODM metadata.',
                $fieldName,
                $index,
            ));
        }

        return $className;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeDocumentArray(mixed $document, string $fieldName, string $index): array
    {
        if (!\is_array($document)) {
            throw new \RuntimeException(\sprintf(
                'Object field "%s" on index "%s" expects an array of documents.',
                $fieldName,
                $index,
            ));
        }

        $normalizedDocument = [];
        foreach ($document as $documentFieldName => $value) {
            if (!\is_string($documentFieldName)) {
                throw new \RuntimeException(\sprintf(
                    'Object field "%s" on index "%s" expects an array document.',
                    $fieldName,
                    $index,
                ));
            }

            $normalizedDocument[$documentFieldName] = $value;
        }

        return $normalizedDocument;
    }
}
