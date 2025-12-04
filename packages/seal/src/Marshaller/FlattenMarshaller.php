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
    private readonly Marshaller $marshaller;

    private readonly Flattener $flattener;

    /**
     * @param array{
     *     name?: string,
     *     latitude?: string|int,
     *     longitude?: string|int,
     *     separator?: string,
     *     multiple?: bool,
     * }|null $geoPointFieldConfig
     * @param non-empty-string $fieldSeparator
     */
    public function __construct(
        private readonly string $dateFormat = 'c',
        private readonly bool $addRawFilterTextField = false,
        private readonly array|null $geoPointFieldConfig = null,
        private readonly string $fieldSeparator = '.',
    ) {
        $this->marshaller = new Marshaller(
            $this->dateFormat,
            $this->addRawFilterTextField,
            $this->geoPointFieldConfig,
        );

        $this->flattener = new Flattener(
            metadataKey: 's_metadata',
            fieldSeparator: $this->fieldSeparator,
        );
    }

    /**
     * @param array<string, Field\AbstractField> $fields
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public function marshall(array $fields, array $document): array
    {
        $marshalledDocument = $this->marshaller->marshall($fields, $document);

        $geoFieldName = $this->findGeoFieldName($fields);
        $geoFieldValue = null;
        if (null !== $geoFieldName && \array_key_exists($geoFieldName, $marshalledDocument)) {
            $geoFieldValue = $marshalledDocument[$geoFieldName];
            unset($marshalledDocument[$geoFieldName]);
        }

        $flattenDocument = $this->flattener->flatten($marshalledDocument);

        if (null !== $geoFieldName) {
            $flattenDocument[$geoFieldName] = $geoFieldValue;
        }

        return $flattenDocument;
    }

    /**
     * @param array<string, Field\AbstractField> $fields
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function unmarshall(array $fields, array $raw): array
    {
        $raw = \array_filter($raw, static fn ($value) => null !== $value);

        $geoFieldName = $this->findGeoFieldName($fields);
        $geoFieldValue = null;
        if (null !== $geoFieldName && \array_key_exists($geoFieldName, $raw)) {
            $geoFieldValue = $raw[$geoFieldName];
            unset($raw[$geoFieldName]);
        }

        $unflattenDocument = $this->flattener->unflatten($raw);

        if (null !== $geoFieldName && null !== $geoFieldValue) {
            $unflattenDocument[$geoFieldName] = $geoFieldValue;
        }

        $unmarshalledDocument = $this->marshaller->unmarshall($fields, $unflattenDocument);

        return $unmarshalledDocument;
    }

    /**
     * @param array<string, Field\AbstractField> $fields
     */
    private function findGeoFieldName(array $fields): string|null
    {
        $geoFieldName = $this->geoPointFieldConfig['name'] ?? null;
        if (null !== $geoFieldName) {
            return $geoFieldName;
        }

        foreach ($fields as $field) {
            if ($field instanceof Field\GeoPointField) {
                return $field->name;
            }
        }

        return null;
    }
}
