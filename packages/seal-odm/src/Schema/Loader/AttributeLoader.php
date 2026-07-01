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

namespace CmsIg\Seal\Odm\Schema\Loader;

use CmsIg\Seal\Odm\Attribute\Field as FieldAttribute;
use CmsIg\Seal\Odm\Attribute\Identifier as IdentifierAttribute;
use CmsIg\Seal\Odm\Attribute\Index as IndexAttribute;
use CmsIg\Seal\Odm\Metadata\PropertyTypeResolver;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Loader\IndexMerger;
use CmsIg\Seal\Schema\Loader\LoaderInterface;
use CmsIg\Seal\Schema\Schema;

final class AttributeLoader implements LoaderInterface
{
    private readonly IndexMerger $indexMerger;
    private readonly PropertyTypeResolver $propertyTypeResolver;

    /**
     * @param string[] $directories
     */
    public function __construct(
        private readonly array $directories,
        private readonly string $indexNamePrefix = '',
    ) {
        $this->indexMerger = new IndexMerger();
        $this->propertyTypeResolver = new PropertyTypeResolver();
    }

    public function load(): Schema
    {
        /** @var array<string, Index> $indexes */
        $indexes = [];

        foreach ($this->directories as $directory) {
            foreach ($this->findPhpFiles($directory) as $filePath) {
                foreach ($this->requireFileAndGetClasses($filePath) as $className) {
                    $reflectionClass = new \ReflectionClass($className);
                    if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait()) {
                        continue;
                    }

                    $indexAttribute = $this->getIndexAttribute($reflectionClass);
                    if (!$indexAttribute instanceof IndexAttribute) {
                        continue;
                    }

                    $indexes = $this->indexMerger->mergeIndexes(
                        $indexes,
                        $this->createIndex($reflectionClass, $indexAttribute),
                        $this->indexNamePrefix,
                    );
                }
            }
        }

        return new Schema($indexes);
    }

    /**
     * @return string[]
     */
    private function findPhpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $files = [];
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if ('php' !== $file->getExtension()) {
                continue;
            }

            $realPath = $file->getRealPath();
            if (false === $realPath) {
                continue;
            }

            $files[] = $realPath;
        }

        \sort($files);

        return $files;
    }

    /**
     * @return list<class-string>
     */
    private function requireFileAndGetClasses(string $filePath): array
    {
        require_once $filePath;

        $classes = [];
        foreach (\get_declared_classes() as $className) {
            $reflectionClass = new \ReflectionClass($className);
            if ($filePath !== $reflectionClass->getFileName()) {
                continue;
            }

            $classes[] = $className;
        }

        return $classes;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function createIndex(\ReflectionClass $reflectionClass, IndexAttribute $indexAttribute): Index
    {
        $options = $indexAttribute->options;
        $options['odm'] = [
            'class' => $reflectionClass->getName(),
        ];

        return new Index(
            $indexAttribute->name,
            $this->createFields($reflectionClass, []),
            $options,
        );
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     * @param list<class-string> $classStack
     *
     * @return array<string, Field\AbstractField>
     */
    private function createFields(\ReflectionClass $reflectionClass, array $classStack): array
    {
        $className = $reflectionClass->getName();
        if (\in_array($className, $classStack, true)) {
            throw new \RuntimeException(\sprintf(
                'Circular object field reference detected for class "%s".',
                $className,
            ));
        }

        $classStack[] = $className;

        $fields = [];
        foreach ($this->getSchemaProperties($reflectionClass) as [$property, $fieldAttribute, $identifierAttribute]) {
            $fieldName = $fieldAttribute instanceof FieldAttribute && null !== $fieldAttribute->name ? $fieldAttribute->name : $property->getName();
            $isIdentifier = $identifierAttribute instanceof IdentifierAttribute;
            $fields[$fieldName] = $this->createField($reflectionClass, $property, $fieldAttribute, $fieldName, $isIdentifier, $classStack);
        }

        return $fields;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     * @param list<class-string> $classStack
     */
    private function createField(
        \ReflectionClass $reflectionClass,
        \ReflectionProperty $property,
        FieldAttribute|null $fieldAttribute,
        string $fieldName,
        bool $isIdentifier,
        array $classStack,
    ): Field\AbstractField {
        $resolvedType = $this->propertyTypeResolver->resolve($property);
        $searchable = $fieldAttribute instanceof FieldAttribute ? $fieldAttribute->searchable : null;
        $filterable = $fieldAttribute instanceof FieldAttribute ? $fieldAttribute->filterable : false;
        $sortable = $fieldAttribute instanceof FieldAttribute ? $fieldAttribute->sortable : false;
        $distinct = $fieldAttribute instanceof FieldAttribute ? $fieldAttribute->distinct : false;
        $facet = $fieldAttribute instanceof FieldAttribute ? $fieldAttribute->facet : false;
        $options = $fieldAttribute instanceof FieldAttribute ? $fieldAttribute->options : [];
        $odmOptions = $this->createFieldMetadata($property, $resolvedType);

        if ($isIdentifier) {
            if ('int' === $resolvedType['kind']) {
                $odmOptions['type'] = $resolvedType['kind'];
            } elseif ('string' !== $resolvedType['kind']) {
                throw new \RuntimeException(\sprintf(
                    'Identifier property "%s" on class "%s" must resolve to string or int.',
                    $property->getName(),
                    $reflectionClass->getName(),
                ));
            }

            return new Field\IdentifierField($fieldName, options: \array_replace_recursive(
                $options,
                [] !== $odmOptions ? ['odm' => $odmOptions] : [],
            ));
        }

        if ('object' === $resolvedType['kind']) {
            if (
                null !== $searchable
                || $filterable
                || $sortable
                || $distinct
                || $facet
            ) {
                throw new \RuntimeException(\sprintf(
                    'Object field "%s" on class "%s" does not support searchable, filterable, sortable, distinct or facet flags.',
                    $property->getName(),
                    $reflectionClass->getName(),
                ));
            }

            $objectClassName = $resolvedType['class'] ?? null;
            if (!\is_string($objectClassName)) {
                throw new \RuntimeException(\sprintf(
                    'Object field "%s" on class "%s" is missing resolved class metadata.',
                    $property->getName(),
                    $reflectionClass->getName(),
                ));
            }

            $objectClass = new \ReflectionClass($objectClassName);

            return new Field\ObjectField(
                $fieldName,
                $this->createFields($objectClass, $classStack),
                multiple: $resolvedType['multiple'],
                options: \array_replace_recursive(
                    $options,
                    [] !== $odmOptions ? ['odm' => $odmOptions] : [],
                ),
            );
        }

        if (true === $searchable) {
            throw new \RuntimeException(\sprintf(
                'Property "%s" on class "%s" does not support searchable=true for inferred type "%s".',
                $property->getName(),
                $reflectionClass->getName(),
                $resolvedType['kind'],
            ));
        }

        return match ($resolvedType['kind']) {
            'string' => new Field\TextField(
                $fieldName,
                multiple: $resolvedType['multiple'],
                searchable: $searchable ?? true,
                filterable: $filterable,
                sortable: $sortable,
                distinct: $distinct,
                facet: $facet,
                options: $options,
            ),
            'int' => new Field\IntegerField(
                $fieldName,
                multiple: $resolvedType['multiple'],
                searchable: false,
                filterable: $filterable,
                sortable: $sortable,
                distinct: $distinct,
                facet: $facet,
                options: $options,
            ),
            'float' => new Field\FloatField(
                $fieldName,
                multiple: $resolvedType['multiple'],
                searchable: false,
                filterable: $filterable,
                sortable: $sortable,
                distinct: $distinct,
                facet: $facet,
                options: $options,
            ),
            'bool' => new Field\BooleanField(
                $fieldName,
                multiple: $resolvedType['multiple'],
                searchable: false,
                filterable: $filterable,
                sortable: $sortable,
                distinct: $distinct,
                facet: $facet,
                options: $options,
            ),
            'datetime' => new Field\DateTimeField(
                $fieldName,
                multiple: $resolvedType['multiple'],
                searchable: false,
                filterable: $filterable,
                sortable: $sortable,
                distinct: $distinct,
                facet: $facet,
                options: $options,
            ),
        };
    }

    /**
     * @param array{kind: 'string'|'int'|'float'|'bool'|'datetime'|'object', multiple: bool, nullable: bool, class?: class-string} $resolvedType
     *
     * @return array<string, mixed>
     */
    private function createFieldMetadata(\ReflectionProperty $property, array $resolvedType): array
    {
        $metadata = [];

        if ('object' === $resolvedType['kind']) {
            $className = $resolvedType['class'] ?? null;
            if (!\is_string($className)) {
                throw new \RuntimeException(\sprintf(
                    'Object field "%s" on class "%s" is missing resolved class metadata.',
                    $property->getName(),
                    $property->getDeclaringClass()->getName(),
                ));
            }

            $metadata['class'] = $className;
        }

        if ('datetime' === $resolvedType['kind']) {
            $hydrateClass = $resolvedType['class'] ?? null;
            if (!\is_string($hydrateClass)) {
                throw new \RuntimeException(\sprintf(
                    'DateTime field "%s" on class "%s" is missing resolved class metadata.',
                    $property->getName(),
                    $property->getDeclaringClass()->getName(),
                ));
            }

            $metadata['hydrateClass'] = $hydrateClass;
        }

        return $metadata;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return list<array{0: \ReflectionProperty, 1: FieldAttribute|null, 2: IdentifierAttribute|null}>
     */
    private function getSchemaProperties(\ReflectionClass $reflectionClass): array
    {
        $properties = [];
        $identifierPropertyName = null;

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
                continue;
            }

            $fieldAttribute = $this->getFieldAttribute($property);
            $identifierAttribute = $this->getIdentifierAttribute($property);
            if (!$fieldAttribute instanceof FieldAttribute && !$identifierAttribute instanceof IdentifierAttribute) {
                continue;
            }

            if ($identifierAttribute instanceof IdentifierAttribute) {
                if (null !== $identifierPropertyName) {
                    throw new \RuntimeException(\sprintf(
                        'Class "%s" can only define one #[Identifier] property, got "%s" and "%s".',
                        $reflectionClass->getName(),
                        $identifierPropertyName,
                        $property->getName(),
                    ));
                }

                $identifierPropertyName = $property->getName();
            }

            $properties[] = [$property, $fieldAttribute, $identifierAttribute];
        }

        return $properties;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function getIndexAttribute(\ReflectionClass $reflectionClass): IndexAttribute|null
    {
        $attributes = $reflectionClass->getAttributes(IndexAttribute::class);
        if ([] === $attributes) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function getFieldAttribute(\ReflectionProperty $property): FieldAttribute|null
    {
        $attributes = $property->getAttributes(FieldAttribute::class);
        if ([] === $attributes) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function getIdentifierAttribute(\ReflectionProperty $property): IdentifierAttribute|null
    {
        $attributes = $property->getAttributes(IdentifierAttribute::class);
        if ([] === $attributes) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
