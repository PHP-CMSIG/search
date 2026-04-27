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

namespace CmsIg\Seal\Search;

/**
 * @internal
 */
final class TypeUtil
{
    /**
     * @param array<string, mixed> $data
     */
    public static function readStringByKey(array $data, string $key, string|null $default = null): string
    {
        $value = $data[$key] ?? $default;
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be a string.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function readNullableStringByKey(array $data, string $key, string|null $default = null): string|null
    {
        $value = $data[$key] ?? $default;
        if (null !== $value && !\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be a string or null.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function readScalarByKey(array $data, string $key): string|int|float|bool
    {
        $value = $data[$key] ?? null;
        if (!\is_string($value) && !\is_int($value) && !\is_float($value) && !\is_bool($value)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be a string, integer, float or boolean.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<mixed>|null $default
     *
     * @return array<mixed>
     */
    public static function readArrayByKey(array $data, string $key, array|null $default = null): array
    {
        $value = $data[$key] ?? $default;
        if (!\is_array($value)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be an array.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $default
     *
     * @return array<string, mixed>
     */
    public static function readStringKeyedArrayByKey(array $data, string $key, array|null $default = null): array
    {
        return self::ensureStringKeyedArray(self::readArrayByKey($data, $key, $default), $key);
    }

    /**
     * @param array<mixed> $value
     *
     * @return array<string, mixed>
     */
    public static function ensureStringKeyedArray(array $value, string $context): array
    {
        $stringKeyedValue = [];
        foreach (\array_keys($value) as $key) {
            if (!\is_string($key)) {
                throw new \InvalidArgumentException(\sprintf('Value for "%s" must be an array with string keys.', $context));
            }

            $stringKeyedValue[$key] = $value[$key];
        }

        return $stringKeyedValue;
    }

    /**
     * @return array<string, mixed>
     */
    public static function ensureStringKeyedArrayValue(mixed $value, string $context): array
    {
        if (!\is_array($value)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be an array.', $context));
        }

        return self::ensureStringKeyedArray($value, $context);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function ensureInstanceOf(mixed $value, string $class, string $context): object
    {
        if (!$value instanceof $class) {
            $actual = \get_debug_type($value);

            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be an instance of "%s", "%s" given.', $context, $class, $actual));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string|int|float|bool>
     */
    public static function readScalarListByKey(array $data, string $key): array
    {
        $values = self::readArrayByKey($data, $key);
        foreach ($values as $index => $value) {
            try {
                $values[$index] = self::readScalarByKey([$key => $value], $key);
            } catch (\InvalidArgumentException $exception) {
                throw new \InvalidArgumentException(\sprintf('Value for "%s" at index "%s" must be a string, integer, float or boolean.', $key, $index), $exception->getCode(), previous: $exception);
            }
        }

        /** @var list<string|int|float|bool> $values */
        $values = \array_values($values);

        return $values;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string>|null $default
     *
     * @return array<string>
     */
    public static function readStringListByKey(array $data, string $key, array|null $default = null): array
    {
        $values = self::readArrayByKey($data, $key, $default);
        foreach ($values as $index => $value) {
            try {
                $values[$index] = self::readStringByKey([$key => $value], $key);
            } catch (\InvalidArgumentException $exception) {
                throw new \InvalidArgumentException(\sprintf('Value for "%s" at index "%s" must be a string.', $key, $index), $exception->getCode(), previous: $exception);
            }
        }

        /** @var array<string> $values */
        $values = \array_values($values);

        return $values;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function readFloatByKey(array $data, string $key): float
    {
        $value = $data[$key] ?? null;
        if (!\is_float($value) && !\is_int($value)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be a number.', $key));
        }

        return (float) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function readIntByKey(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        if (!\is_int($value)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be an integer.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function readNonNegativeIntByKey(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $default;
        if (!\is_int($value) || $value < 0) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be a non-negative integer.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function readNullableNonNegativeIntByKey(array $data, string $key, int|null $default = null): int|null
    {
        $value = $data[$key] ?? $default;
        if (null !== $value && (!\is_int($value) || $value < 0)) {
            throw new \InvalidArgumentException(\sprintf('Value for "%s" must be a non-negative integer or null.', $key));
        }

        return $value;
    }
}
