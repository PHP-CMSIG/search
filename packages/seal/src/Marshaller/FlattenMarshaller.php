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

namespace CmsIg\Seal\Marshaller;

use CmsIg\Seal\Schema\Field;

/**
 * @internal This class currently in discussion to be open for all adapters.
 *
 * The FlattenMarshaller will flatten all fields and save original document under a `_source` field.
 * The FlattenMarshaller should only be used when the Search Engine does not support nested objects and so
 *     the Marshaller should used in many cases instead.
 */
final class FlattenMarshaller
{
    /**
     * @param array{
     *     name?: string,
     *     latitude?: string|int,
     *     longitude?: string|int,
     *     separator?: string,
     *     multiple?: bool,
     * }|null $geoPointFieldConfig
     */
    public function __construct(
        private readonly bool $dateAsInteger = false,
        private readonly bool $addRawFilterTextField = false,
        private readonly string $separator = '.',
        private readonly string $sourceField = '_source',
        private readonly array|null $geoPointFieldConfig = null,
    ) {
    }

    /**
     * @param Field\AbstractField[] $fields
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public function marshall(array $fields, array $document): array
    {
        $flattenDocument = $this->flatten($fields, $document);
        $flattenDocument[$this->sourceField] = \json_encode($document, \JSON_THROW_ON_ERROR);

        return $flattenDocument;
    }

    /**
     * @param Field\AbstractField[] $fields
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function unmarshall(array $fields, array $raw): array
    {
        /** @var array<string, mixed> */
        return \json_decode($raw[$this->sourceField], true, flags: \JSON_THROW_ON_ERROR); // @phpstan-ignore-line
    }

    /**
     * @param Field\AbstractField[] $fields
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function flatten(array $fields, array $raw, bool $rootIsParentMultiple = false)
    {
        foreach ($fields as $name => $field) {
            if (!\array_key_exists($name, $raw)) {
                continue;
            }

            match (true) {
                $field instanceof Field\DateTimeField => $raw[$name] = $this->flattenDateTime($raw[$field->name], $field), // @phpstan-ignore-line
                $field instanceof Field\GeoPointField => $raw[$name] = $this->flattenGeoPointField($raw[$field->name], $field), // @phpstan-ignore-line
                $field instanceof Field\ObjectField => $raw = $this->flattenObject($name, $raw, $field, $rootIsParentMultiple),
                $field instanceof Field\TypedField => $raw = $this->flattenTyped($name, $raw, $field, $rootIsParentMultiple),
                default => null,
            };

            if ($this->addRawFilterTextField
                && $field instanceof Field\TextField && $field->searchable && ($field->sortable || $field->filterable)
            ) {
                $raw[$name . '.raw'] = $raw[$name];
            }
        }

        return $raw;
    }

    /**
     * @param string|string[]|null $value
     *
     * @return int|string|string[]|int[]|null
     */
    private function flattenDateTime(string|array|null $value, Field\DateTimeField $field): int|string|array|null
    {
        if ($field->multiple) {
            /** @var string[]|null $value */

            return \array_map(function ($value) {
                if (null !== $value && $this->dateAsInteger) {
                    /** @var int */
                    return \strtotime($value);
                }

                return $value;
            }, (array) $value);
        }

        /** @var string|null $value */
        if (null !== $value && $this->dateAsInteger) {
            /** @var int */
            return \strtotime($value);
        }

        return $value;
    }

    /**
     * @param array{latitude: float, longitude: float}|null $value
     *
     * @return array<int|string, array<int|string, float>|float|string>|string|null
     */
    private function flattenGeoPointField(array|null $value, Field\GeoPointField $field): array|string|null
    {
        if ($field->multiple) {
            throw new \LogicException('GeoPointField currently does not support multiple values.');
        }

        if ($value) {
            $value = [
                $this->geoPointFieldConfig['latitude'] ?? 'latitude' => $value['latitude'],
                $this->geoPointFieldConfig['longitude'] ?? 'longitude' => $value['longitude'],
            ];

            \ksort($value); // consistent to the Marshaller where we need this for the redisearch

            if ($this->geoPointFieldConfig['separator'] ?? false) {
                $value = \implode($this->geoPointFieldConfig['separator'], $value);
            }

            if ($this->geoPointFieldConfig['multiple'] ?? false) {
                $value = [$value];
            }

            return $value;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function flattenObject(string $name, array $raw, Field\ObjectField $field, bool $rootIsParentMultiple)
    {
        /** @var array<array<string, mixed>> $objects */
        $objects = $field->multiple ? $raw[$name] : [$raw[$name]];

        $newRawData = [];
        foreach ($objects as $object) {
            $isParentMultiple = $rootIsParentMultiple || $field->multiple;
            $flattenedObject = $this->flatten($field->fields, $object, $isParentMultiple);

            foreach ($flattenedObject as $key => $value) {
                $flattenKey = $name . $this->separator . $key;

                if (!$isParentMultiple) {
                    $newRawData[$flattenKey] = $value;

                    continue;
                }

                if (!isset($newRawData[$flattenKey])) {
                    $newRawData[$flattenKey] = [];
                }

                if (!\is_array($value)) {
                    $newRawData[$flattenKey][] = $value; // @phpstan-ignore-line

                    continue;
                }

                foreach ($value as $valuePart) {
                    $newRawData[$flattenKey][] = $valuePart; // @phpstan-ignore-line
                }
            }
        }

        $keepOrderRaw = [];
        foreach ($raw as $key => $value) {
            if ($key === $name) {
                foreach ($newRawData as $key2 => $value2) {
                    $keepOrderRaw[$key2] = $value2;
                }

                continue;
            }

            $keepOrderRaw[$key] = $value;
        }

        return $keepOrderRaw;
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function flattenTyped(string $name, array $raw, Field\TypedField $field, bool $rootIsParentMultiple)
    {
        /** @var array<array<string, mixed>> $objects */
        $objects = $field->multiple ? $raw[$name] : [$raw[$name]];

        $newRawData = [];
        foreach ($objects as $object) {
            /** @var string $type */
            $type = $object[$field->typeField];
            unset($object[$field->typeField]);

            $isParentMultiple = $rootIsParentMultiple || $field->multiple;

            if (!isset($field->types[$type])) {
                throw new \RuntimeException(\sprintf(
                    'Type "%s" not found. Existing types are "%s"',
                    $type,
                    \implode('", "', \array_keys($field->types)),
                ));
            }

            $flattenedObject = $this->flatten($field->types[$type], $object, $isParentMultiple);
            foreach ($flattenedObject as $key => $value) {
                $flattenKey = $name . $this->separator . $type . $this->separator . $key;

                if (!$isParentMultiple) {
                    $newRawData[$flattenKey] = $value;

                    continue;
                }

                if (!isset($newRawData[$flattenKey])) {
                    $newRawData[$flattenKey] = [];
                }

                if (!\is_array($value)) {
                    $newRawData[$flattenKey][] = $value; // @phpstan-ignore-line

                    continue;
                }

                foreach ($value as $valuePart) {
                    $newRawData[$flattenKey][] = $valuePart; // @phpstan-ignore-line
                }
            }
        }

        $keepOrderRaw = [];
        foreach ($raw as $key => $value) {
            if ($key === $name) {
                foreach ($newRawData as $key2 => $value2) {
                    $keepOrderRaw[$key2] = $value2;
                }

                continue;
            }

            $keepOrderRaw[$key] = $value;
        }

        return $keepOrderRaw;
    }
}
