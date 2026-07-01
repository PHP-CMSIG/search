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

namespace CmsIg\Seal\Odm\Metadata;

/**
 * @internal this class is only intended to be used internally by the Seal ODM package
 *
 * @phpstan-type ResolvedPropertyType array{kind: 'string'|'int'|'float'|'bool'|'datetime'|'object', multiple: bool, nullable: bool, class?: class-string}
 */
final class PropertyTypeResolver
{
    /**
     * @var array<string, array<string, class-string>>
     */
    private array $useStatementsByFile = [];

    /**
     * @var array<string, ResolvedPropertyType>
     */
    private array $resolvedTypes = [];

    /**
     * @return ResolvedPropertyType
     */
    public function resolve(\ReflectionProperty $property): array
    {
        $cacheKey = $property->getDeclaringClass()->getName() . '::$' . $property->getName();
        if (isset($this->resolvedTypes[$cacheKey])) {
            return $this->resolvedTypes[$cacheKey];
        }

        $reflectionClass = $property->getDeclaringClass();
        $nativeType = $property->getType();
        if (!$nativeType instanceof \ReflectionType) {
            $phpDocType = $this->extractPhpDocVarType($property);
            if (null === $phpDocType) {
                throw new \RuntimeException(\sprintf(
                    'Property "%s" on class "%s" must have a native type or a supported @var type declaration.',
                    $property->getName(),
                    $reflectionClass->getName(),
                ));
            }

            return $this->resolvedTypes[$cacheKey] = $this->normalizeResolvedType(
                $reflectionClass,
                $property,
                $phpDocType['type'],
                $phpDocType['multiple'],
                $phpDocType['nullable'],
            );
        }

        $nullable = $nativeType->allowsNull();
        $namedType = $this->resolveNamedType($reflectionClass, $property, $nativeType);

        if ('array' === $namedType->getName()) {
            return $this->resolvedTypes[$cacheKey] = $this->resolveArrayPropertyType($reflectionClass, $property, $nullable);
        }

        return $this->resolvedTypes[$cacheKey] = $this->normalizeResolvedType(
            $reflectionClass,
            $property,
            $this->resolveNamedTypeName($reflectionClass, $namedType->getName()),
            false,
            $nullable,
        );
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function resolveNamedType(
        \ReflectionClass $reflectionClass,
        \ReflectionProperty $property,
        \ReflectionType $nativeType,
    ): \ReflectionNamedType {
        if ($nativeType instanceof \ReflectionNamedType) {
            return $nativeType;
        }

        if ($nativeType instanceof \ReflectionUnionType) {
            $types = [];
            foreach ($nativeType->getTypes() as $type) {
                if ($type instanceof \ReflectionNamedType && 'null' === $type->getName()) {
                    continue;
                }

                $types[] = $type;
            }

            if (1 === \count($types) && $types[0] instanceof \ReflectionNamedType) {
                return $types[0];
            }
        }

        throw new \RuntimeException(\sprintf(
            'Property "%s" on class "%s" uses an unsupported native type.',
            $property->getName(),
            $reflectionClass->getName(),
        ));
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return ResolvedPropertyType
     */
    private function resolveArrayPropertyType(\ReflectionClass $reflectionClass, \ReflectionProperty $property, bool $nullable): array
    {
        $phpDocType = $this->extractPhpDocVarType($property);
        if (null === $phpDocType || !$phpDocType['multiple']) {
            throw new \RuntimeException(\sprintf(
                'Array property "%s" on class "%s" requires a supported @var element type declaration.',
                $property->getName(),
                $reflectionClass->getName(),
            ));
        }

        return $this->normalizeResolvedType(
            $reflectionClass,
            $property,
            $phpDocType['type'],
            true,
            $nullable || $phpDocType['nullable'],
        );
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return ResolvedPropertyType
     */
    private function normalizeResolvedType(
        \ReflectionClass $reflectionClass,
        \ReflectionProperty $property,
        string $typeName,
        bool $multiple,
        bool $nullable,
    ): array {
        return match ($typeName) {
            'string' => ['kind' => 'string', 'multiple' => $multiple, 'nullable' => $nullable],
            'int' => ['kind' => 'int', 'multiple' => $multiple, 'nullable' => $nullable],
            'float' => ['kind' => 'float', 'multiple' => $multiple, 'nullable' => $nullable],
            'bool' => ['kind' => 'bool', 'multiple' => $multiple, 'nullable' => $nullable],
            \DateTimeInterface::class => ['kind' => 'datetime', 'multiple' => $multiple, 'nullable' => $nullable, 'class' => \DateTimeImmutable::class],
            \DateTime::class => ['kind' => 'datetime', 'multiple' => $multiple, 'nullable' => $nullable, 'class' => \DateTime::class],
            \DateTimeImmutable::class => ['kind' => 'datetime', 'multiple' => $multiple, 'nullable' => $nullable, 'class' => \DateTimeImmutable::class],
            'array', 'iterable', 'mixed' => throw new \RuntimeException(\sprintf(
                'Property "%s" on class "%s" uses unsupported type "%s".',
                $property->getName(),
                $reflectionClass->getName(),
                $typeName,
            )),
            default => $this->normalizeResolvedObjectType($reflectionClass, $property, $typeName, $multiple, $nullable),
        };
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return array{kind: 'object', multiple: bool, nullable: bool, class: class-string}
     */
    private function normalizeResolvedObjectType(
        \ReflectionClass $reflectionClass,
        \ReflectionProperty $property,
        string $typeName,
        bool $multiple,
        bool $nullable,
    ): array {
        if (\enum_exists($typeName)) {
            throw new \RuntimeException(\sprintf(
                'Enum property "%s" on class "%s" is not supported.',
                $property->getName(),
                $reflectionClass->getName(),
            ));
        }

        if (!\class_exists($typeName)) {
            throw new \RuntimeException(\sprintf(
                'Property "%s" on class "%s" references unknown type "%s".',
                $property->getName(),
                $reflectionClass->getName(),
                $typeName,
            ));
        }

        /** @var class-string $typeName */
        return ['kind' => 'object', 'multiple' => $multiple, 'nullable' => $nullable, 'class' => $typeName];
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function resolveNamedTypeName(\ReflectionClass $reflectionClass, string $typeName): string
    {
        return match ($typeName) {
            'self', 'static' => $reflectionClass->getName(),
            'parent' => $this->resolveParentClassName($reflectionClass),
            default => $typeName,
        };
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function resolveParentClassName(\ReflectionClass $reflectionClass): string
    {
        $parentClass = $reflectionClass->getParentClass();
        if (false === $parentClass) {
            throw new \RuntimeException(\sprintf(
                'Class "%s" uses parent type without a parent class.',
                $reflectionClass->getName(),
            ));
        }

        return $parentClass->getName();
    }

    /**
     * @return array{type: string, multiple: bool, nullable: bool}|null
     */
    private function extractPhpDocVarType(\ReflectionProperty $property): array|null
    {
        $docComment = $property->getDocComment();
        if (false === $docComment) {
            return null;
        }

        if (!\preg_match('/@var\s+([^\s\*]+)/', $docComment, $matches)) {
            return null;
        }

        $declaredType = $matches[1];
        $rawTypes = \array_map(trim(...), \explode('|', $declaredType));
        $nullable = \in_array('null', $rawTypes, true);
        $types = \array_values(\array_filter($rawTypes, static fn (string $type): bool => '' !== $type && 'null' !== $type));
        if (1 !== \count($types)) {
            return null;
        }

        $type = \trim($types[0]);

        if (\preg_match('/^(.+)\[\]$/', $type, $arrayMatches)) {
            return [
                'type' => $this->resolvePhpDocTypeName($property->getDeclaringClass(), \trim($arrayMatches[1])),
                'multiple' => true,
                'nullable' => $nullable,
            ];
        }

        if (\preg_match('/^(array|list)<\s*(.+)\s*>$/', $type, $arrayMatches)) {
            $genericType = \trim($arrayMatches[2]);
            $genericTypes = \array_map(trim(...), \explode(',', $genericType));
            $elementType = $genericTypes[\count($genericTypes) - 1];

            return [
                'type' => $this->resolvePhpDocTypeName($property->getDeclaringClass(), $elementType),
                'multiple' => true,
                'nullable' => $nullable,
            ];
        }

        return [
            'type' => $this->resolvePhpDocTypeName($property->getDeclaringClass(), $type),
            'multiple' => false,
            'nullable' => $nullable,
        ];
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function resolvePhpDocTypeName(\ReflectionClass $reflectionClass, string $typeName): string
    {
        if (\in_array($typeName, ['string', 'int', 'float', 'bool', 'mixed'], true)) {
            return $typeName;
        }

        if (\in_array($typeName, ['self', 'static', 'parent'], true)) {
            return $this->resolveNamedTypeName($reflectionClass, $typeName);
        }

        if (\str_starts_with($typeName, '\\')) {
            return \ltrim($typeName, '\\');
        }

        if (\class_exists($typeName)) {
            return $typeName;
        }

        $uses = $this->getUseStatements($reflectionClass);
        $firstNamespaceSeparator = \strpos($typeName, '\\');
        $firstSegment = false === $firstNamespaceSeparator ? $typeName : \substr($typeName, 0, $firstNamespaceSeparator);
        $remainingPath = false === $firstNamespaceSeparator ? '' : \substr($typeName, $firstNamespaceSeparator);
        if (isset($uses[$firstSegment])) {
            return $uses[$firstSegment] . $remainingPath;
        }

        if ('' !== $reflectionClass->getNamespaceName()) {
            return $reflectionClass->getNamespaceName() . '\\' . $typeName;
        }

        return $typeName;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return array<string, class-string>
     */
    private function getUseStatements(\ReflectionClass $reflectionClass): array
    {
        $fileName = $reflectionClass->getFileName();
        if (false === $fileName) {
            return [];
        }

        if (isset($this->useStatementsByFile[$fileName])) {
            return $this->useStatementsByFile[$fileName];
        }

        $code = \file_get_contents($fileName);
        if (false === $code) {
            return [];
        }

        $uses = [];
        $tokens = \token_get_all($code);
        $depth = 0;

        for ($index = 0, $count = \count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];

            if (\is_string($token)) {
                if ('{' === $token) {
                    ++$depth;
                } elseif ('}' === $token) {
                    --$depth;
                }

                continue;
            }

            if (0 !== $depth || \T_USE !== $token[0]) {
                continue;
            }

            $statement = '';
            for (++$index; $index < $count; ++$index) {
                $nextToken = $tokens[$index];

                if (\is_string($nextToken) && ';' === $nextToken) {
                    break;
                }

                $statement .= \is_array($nextToken) ? $nextToken[1] : $nextToken;
            }

            foreach (\explode(',', $statement) as $useStatement) {
                $useStatement = \trim($useStatement);
                if ('' === $useStatement || \str_contains($useStatement, '{')) {
                    continue;
                }

                $aliasParts = \preg_split('/\s+as\s+/i', $useStatement);
                if (false === $aliasParts || [] === $aliasParts) {
                    continue;
                }

                $className = \trim($aliasParts[0], " \t\n\r\0\x0B\\");
                $alias = isset($aliasParts[1]) ? \trim($aliasParts[1]) : null;

                if ('' === $className) {
                    continue;
                }

                $shortName = null !== $alias && '' !== $alias
                    ? $alias
                    : \substr($className, (int) \strrpos($className, '\\') + 1);

                if (!\is_string($shortName) || '' === $shortName) {
                    continue;
                }

                /** @var class-string $normalizedClassName */
                $normalizedClassName = $className;
                $uses[$shortName] = $normalizedClassName;
            }
        }

        return $this->useStatementsByFile[$fileName] = $uses;
    }
}
