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
 * The Marshaller will split the typed fields into different subfields and use a `_originalIndex` field to unmarshall it again.
 */
final class Marshaller
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
        $rawDocument = [];

        foreach ($fields as $name => $field) {
            if (!\array_key_exists($field->name, $document)) {
                continue;
            }

            if ($field->multiple && !\is_array($document[$field->name])) {
                throw new \RuntimeException('Field "' . $field->name . '" is multiple but value is not an array.');
            }

            match (true) {
                $field instanceof Field\ObjectField => $rawDocument[$name] = $this->marshallObjectFields($document[$field->name], $field), // @phpstan-ignore-line
                $field instanceof Field\TypedField => $rawDocument = \array_replace($rawDocument, $this->marhsallTypedFields($name, $document[$field->name], $field)), // @phpstan-ignore-line
                $field instanceof Field\DateTimeField => $rawDocument[$name] = $this->marshallDateTimeField($document[$field->name], $field), // @phpstan-ignore-line
                $field instanceof Field\GeoPointField => $rawDocument[$this->geoPointFieldConfig['name'] ?? $name] = $this->marshallGeoPointField($document[$field->name], $field), // @phpstan-ignore-line
                default => $rawDocument[$name] = $document[$field->name],
            };

            if ($this->addRawFilterTextField
                && $field instanceof Field\TextField && $field->searchable && ($field->sortable || $field->filterable)
            ) {
                $rawDocument[$name . '.raw'] = $rawDocument[$name];
            }
        }

        return $rawDocument;
    }

    /**
     * @param array{latitude: float, longitude: float}|null $value
     *
     * @return array<int|string, array<int|string, float>|float|string>|string|null
     */
    private function marshallGeoPointField(array|null $value, Field\GeoPointField $field): array|string|null
    {
        if ($field->multiple) {
            throw new \LogicException('GeoPointField currently does not support multiple values.');
        }

        if ($value) {
            $value = [
                $this->geoPointFieldConfig['latitude'] ?? 'latitude' => $value['latitude'],
                $this->geoPointFieldConfig['longitude'] ?? 'longitude' => $value['longitude'],
            ];

            \ksort($value); // for redisearch we need invert the order

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
     * @param string|string[]|null $value
     *
     * @return int|string|string[]|int[]|null
     */
    private function marshallDateTimeField(string|array|null $value, Field\DateTimeField $field): int|string|array|null
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
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>|array<array<string, mixed>>
     */
    private function marshallObjectFields(array $document, Field\ObjectField $field): array
    {
        if (!$field->multiple) {
            return $this->marshall($field->fields, $document);
        }

        /** @var array<array<string, mixed>> $rawDocuments */
        $rawDocuments = [];
        /** @var array<string, mixed> $data */
        foreach ($document as $data) {
            $rawDocuments[] = $this->marshall($field->fields, $data);
        }

        return $rawDocuments;
    }

    /**
     * @param array<string, mixed>|array<int, array<string, mixed>> $document
     *
     * @return array<string, mixed>
     */
    private function marhsallTypedFields(string $name, array $document, Field\TypedField $field): array
    {
        $rawFields = [];

        if (!$field->multiple) {
            $document = [$document];
        }

        /** @var array<string, mixed> $data */
        foreach ($document as $originalIndex => $data) {
            /** @var string|null $type */
            $type = $data[$field->typeField] ?? null;
            if (null === $type || !\array_key_exists($type, $field->types)) {
                throw new \RuntimeException('Expected type field "' . $field->typeField . '" not found in document.');
            }

            $typedFields = $field->types[$type];

            $rawData = \array_replace($field->multiple ? [
                '_originalIndex' => $originalIndex,
            ] : [], $this->marshall($typedFields, $data));

            if ($field->multiple) {
                $rawFields[$name][$type][] = $rawData;

                continue;
            }

            $rawFields[$name][$type] = $rawData;
        }

        return $rawFields;
    }

    /**
     * @param Field\AbstractField[] $fields
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function unmarshall(array $fields, array $raw): array
    {
        $document = [];

        foreach ($fields as $name => $field) {
            if (
                (
                    !\array_key_exists($name, $raw)
                    && !$field instanceof Field\TypedField
                )
                && (!$field instanceof Field\GeoPointField || !\array_key_exists($this->geoPointFieldConfig['name'] ?? $name, $raw))
            ) {
                continue;
            }

            match (true) {
                $field instanceof Field\ObjectField => $document[$field->name] = $this->unmarshallObjectFields($raw[$name], $field), // @phpstan-ignore-line
                $field instanceof Field\TypedField => $document = \array_replace($document, $this->unmarshallTypedFields($name, $raw, $field)),
                $field instanceof Field\DateTimeField => $document[$name] = $this->unmarshallDateTimeField($raw[$field->name], $field), // @phpstan-ignore-line
                $field instanceof Field\GeoPointField => $document[$name] = $this->unmarshallGeoPointField($raw, $field),
                default => $document[$field->name] = $raw[$name] ?? ($field->multiple ? [] : null),
            };
        }

        return $document;
    }

    /**
     * @param array<string, mixed>|array<array<string, mixed>> $raw
     *
     * @return array<string, mixed>
     */
    private function unmarshallTypedFields(string $name, array $raw, Field\TypedField $field): array
    {
        $documentFields = [];

        foreach ($field->types as $type => $typedFields) {
            if (!isset($raw[$name][$type])) { // @phpstan-ignore-line
                continue;
            }

            /** @var array<array<string, mixed>> $dataList */
            $dataList = $field->multiple ? $raw[$name][$type] : [$raw[$name][$type]];

            foreach ($dataList as $data) {
                $documentData = \array_replace([$field->typeField => $type], $this->unmarshall($typedFields, $data));

                if ($field->multiple) {
                    /** @var string|int|null $originalIndex */
                    $originalIndex = $data['_originalIndex'] ?? null;
                    if (null === $originalIndex) {
                        throw new \RuntimeException('Expected "_originalIndex" field not found in document.');
                    }

                    $documentFields[$name][$originalIndex] = $documentData;

                    \ksort($documentFields[$name]);

                    continue;
                }

                $documentFields[$name] = $documentData;
            }
        }

        return $documentFields;
    }

    /**
     * @param array<string, mixed>|array<array<string, mixed>> $raw
     *
     * @return array<string, mixed>|array<array<string, mixed>>
     */
    private function unmarshallObjectFields(array $raw, Field\ObjectField $field): array
    {
        if (!$field->multiple) {
            return $this->unmarshall($field->fields, $raw);
        }

        /** @var array<array<string, mixed>> $documentFields */
        $documentFields = [];

        /** @var array<string, mixed> $data */
        foreach ($raw as $data) {
            $documentFields[] = $this->unmarshall($field->fields, $data);
        }

        return $documentFields;
    }

    /**
     * @param string|int|string[]|int[]|null $value
     *
     * @return string|string[]
     */
    private function unmarshallDateTimeField(string|int|array|null $value, Field\DateTimeField $field): string|array|null
    {
        if ($field->multiple) {
            return \array_map(function ($value) {
                if (null !== $value && $this->dateAsInteger) {
                    /** @var int $value */

                    return \date('c', $value);
                }

                /** @var string */
                return $value;
            }, (array) $value);
        }

        if (null !== $value && $this->dateAsInteger) {
            /** @var int $value */

            return \date('c', $value);
        }

        /** @var string|null */
        return $value;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array{latitude: float, longitude: float}|null
     */
    private function unmarshallGeoPointField(array $document, Field\GeoPointField $field): array|null
    {
        if ($field->multiple) {
            throw new \LogicException('GeoPointField currently does not support multiple values.');
        }

        /** @var array<int|string, array<int|string, float>|float|string>|string|null $value */
        $value = $document[$this->geoPointFieldConfig['name'] ?? $field->name] ?? null;

        if ($value) {
            if ($this->geoPointFieldConfig['multiple'] ?? false) {
                $value = $value[0] ?? $value;
            }

            $separator = $this->geoPointFieldConfig['separator'] ?? false;
            if ($separator) {
                \assert(\is_string($value), 'Expected to have a string value for a seperated geo point field.');
                $value = \explode($separator, $value);
            }

            $latitude = $value[$this->geoPointFieldConfig['latitude'] ?? 'latitude'] ?? null;
            $longitude = $value[$this->geoPointFieldConfig['longitude'] ?? 'longitude'] ?? null;

            \assert(null !== $latitude && null !== $longitude, 'Expected to have latitude and longitude in geo point field.');

            return [
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];
        }

        return null;
    }
}
