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

namespace CmsIg\Seal\Adapter\Memory;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;

final class MemorySearcher implements SearcherInterface
{
    private readonly Marshaller $marshaller;

    public function __construct()
    {
        $this->marshaller = new Marshaller();
    }

    public function search(Search $search): Result
    {
        $documents = [];

        /** @var Index $index */
        foreach ([$search->index] as $index) {
            $indexDocuments = MemoryStorage::getDocuments($index);

            if ([] === $search->filters) {
                $documents = [...$documents, ...$indexDocuments];

                continue;
            }

            foreach ($search->filters as $filter) {
                $indexDocuments = $this->filterDocuments($index, $indexDocuments, $filter);
            }

            foreach ($indexDocuments as $document) {
                $documents[] = $this->marshaller->unmarshall($index->fields, $document);
            }
        }

        $sortBys = \array_reverse($search->sortBys);
        foreach ($sortBys as $field => $direction) {
            \usort($documents, function ($docA, $docB) use ($field, $direction) {
                if ('desc' === $direction) {
                    return $docB[$field] <=> $docA[$field];
                }

                return $docA[$field] <=> $docB[$field];
            });
        }

        $documents = \array_slice($documents, $search->offset, $search->limit);

        $generator = (function () use ($documents): \Generator {
            foreach ($documents as $document) {
                yield $document;
            }
        });

        return new Result(
            $generator(),
            \count($documents),
        );
    }

    /**
     * @param array<array<string, mixed>> $documents
     *
     * @return array<array<string, mixed>>
     */
    private function filterDocuments(Index $index, array $documents, object $filter): array
    {
        $filteredDocuments = [];

        foreach ($documents as $identifier => $document) {
            $identifier = (string) $identifier;

            if ($filter instanceof Condition\IdentifierCondition) {
                if ($filter->identifier !== $identifier) {
                    continue;
                }
            } elseif ($filter instanceof Condition\SearchCondition) {
                $searchableDocument = $this->getSearchableDocument($index->fields, $document);

                $text = \json_encode($searchableDocument, \JSON_THROW_ON_ERROR);
                $terms = \explode(' ', $filter->query);

                foreach ($terms as $term) {
                    if (!\str_contains($text, $term)) {
                        continue 2;
                    }
                }
            } elseif ($filter instanceof Condition\EqualCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);

                if (!\in_array($filter->value, $values, true)) {
                    continue;
                }
            } elseif ($filter instanceof Condition\InCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);

                if ([] === \array_intersect($filter->values, $values)) {
                    continue;
                }
            } elseif ($filter instanceof Condition\NotInCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);

                if ([] !== \array_intersect($filter->values, $values)) {
                    continue;
                }
            } elseif ($filter instanceof Condition\NotEqualCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);

                if (\in_array($filter->value, $values, true)) {
                    continue;
                }
            } elseif ($filter instanceof Condition\GreaterThanCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);
                $hasMatchingValue = false;
                foreach ($values as $value) {
                    if ($value > $filter->value) {
                        $hasMatchingValue = true;
                    }
                }

                if (false === $hasMatchingValue) {
                    continue;
                }
            } elseif ($filter instanceof Condition\GreaterThanEqualCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);

                $hasMatchingValue = false;
                foreach ($values as $value) {
                    if ($value >= $filter->value) {
                        $hasMatchingValue = true;
                    }
                }

                if (false === $hasMatchingValue) {
                    continue;
                }
            } elseif ($filter instanceof Condition\LessThanCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);

                $hasMatchingValue = false;
                foreach ($values as $value) {
                    if ($value < $filter->value) {
                        $hasMatchingValue = true;
                    }
                }

                if (false === $hasMatchingValue) {
                    continue;
                }
            } elseif ($filter instanceof Condition\LessThanEqualCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);

                $hasMatchingValue = false;
                foreach ($values as $value) {
                    if ($value <= $filter->value) {
                        $hasMatchingValue = true;
                    }
                }

                if (false === $hasMatchingValue) {
                    continue;
                }
            } elseif ($filter instanceof Condition\GeoDistanceCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);
                if (isset($values['latitude'])) {
                    $values = [$values];
                }

                $hasMatchingValue = false;
                foreach ($values as $value) {
                    if (!\is_array($value)
                        || !isset($value['latitude'])
                        || !isset($value['longitude'])
                    ) {
                        continue;
                    }

                    $distance = $this->distanceBetween(
                        $filter->latitude,
                        $filter->longitude,
                        $value['latitude'],
                        $value['longitude'],
                    );

                    if ($distance <= $filter->distance) {
                        $hasMatchingValue = true;
                    }
                }

                if (false === $hasMatchingValue) {
                    continue;
                }
            } elseif ($filter instanceof Condition\GeoBoundingBoxCondition) {
                if (\str_contains($filter->field, '.')) {
                    throw new \RuntimeException('Nested fields are not supported yet.');
                }

                $values = (array) ($document[$filter->field] ?? []);
                if (isset($values['latitude'])) {
                    $values = [$values];
                }

                $hasMatchingValue = false;
                foreach ($values as $value) {
                    if (!\is_array($value)
                        || !isset($value['latitude'])
                        || !isset($value['longitude'])
                    ) {
                        continue;
                    }

                    $isInsideBox = $this->coordinatesInsideBox(
                        $value['latitude'],
                        $value['longitude'],
                        $filter->northLatitude,
                        $filter->eastLongitude,
                        $filter->southLatitude,
                        $filter->westLongitude,
                    );

                    if ($isInsideBox) {
                        $hasMatchingValue = true;
                    }
                }

                if (false === $hasMatchingValue) {
                    continue;
                }
            } elseif ($filter instanceof Condition\AndCondition) {
                $subDocuments = [];
                foreach ($filter->conditions as $subFilter) {
                    $subDocuments = [...$subDocuments, ...$this->filterDocuments($index, [$document], $subFilter)];
                }

                if (\count($filter->conditions) !== \count($subDocuments)) {
                    continue;
                }
            } elseif ($filter instanceof Condition\OrCondition) {
                $subDocuments = [];
                foreach ($filter->conditions as $subFilter) {
                    $subDocuments = [...$subDocuments, ...$this->filterDocuments($index, [$document], $subFilter)];
                }

                if ([] === $subDocuments) {
                    continue;
                }
            } else {
                throw new \LogicException($filter::class . ' filter not implemented.');
            }

            $filteredDocuments[] = $document;
        }

        return $filteredDocuments;
    }

    /**
     * Returns true or false if coordinates are inside the box.
     */
    private function coordinatesInsideBox(
        float $latitude,
        float $longitude,
        float $northLatitude,
        float $eastLongitude,
        float $southLatitude,
        float $westLongitude,
    ): bool {
        // Check if the latitude is between the north and south boundaries
        $isWithinLatitude = $latitude <= $northLatitude && $latitude >= $southLatitude;

        // Check if the longitude is between the west and east boundaries
        $isWithinLongitude = $longitude >= $westLongitude && $longitude <= $eastLongitude;

        // The point is inside the bounding box if both conditions are true
        return $isWithinLatitude && $isWithinLongitude;
    }

    /**
     * Returns a distance in meters.
     */
    private function distanceBetween(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo): int
    {
        $latFrom = \deg2rad($latitudeFrom);
        $lonFrom = \deg2rad($longitudeFrom);
        $latTo = \deg2rad($latitudeTo);
        $lonTo = \deg2rad($longitudeTo);

        // Haversine formula.
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * \asin(\sqrt(\sin($latDelta / 2) ** 2 +
                \cos($latFrom) * \cos($latTo) * \sin($lonDelta / 2) ** 2));

        $earthRadius = 6_371_000;

        $distance = $earthRadius * $angle;

        return (int) $distance;
    }

    /**
     * @param Field\AbstractField[] $fields
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function getSearchableDocument(array $fields, array $document): array
    {
        foreach ($fields as $field) {
            if (!isset($document[$field->name])) {
                continue;
            }

            if (!$field->searchable) {
                unset($document[$field->name]);

                continue;
            }

            match (true) {
                $field instanceof Field\ObjectField => $document[$field->name] = $this->getSearchableObjectFields($field, $document[$field->name]), // @phpstan-ignore-line
                $field instanceof Field\TypedField => $document[$field->name] = $this->getSearchableTypedFields($field, $document[$field->name]), // @phpstan-ignore-line
                default => null,
            };
        }

        return $document;
    }

    /**
     * @param array<string, mixed>|array<array<string, mixed>> $data
     *
     * @return array<string, mixed>|array<array<string, mixed>>
     */
    private function getSearchableObjectFields(Field\ObjectField $field, array $data)
    {
        if (!$field->multiple) {
            return $this->getSearchableDocument($field->fields, $data);
        }

        /** @var array<array<string, mixed>> $documents */
        $documents = [];

        /** @var array<string, mixed> $sub */
        foreach ($data as $sub) {
            $documents[] = $this->getSearchableDocument($field->fields, $sub);
        }

        return $documents;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function getSearchableTypedFields(Field\TypedField $field, array $data)
    {
        $documents = [];
        foreach ($data as $type => $sub) {
            if (!$field->multiple) {
                $sub = [$sub];
            }

            /** @var array<array<string, mixed>> $sub */
            $typeFields = $field->types[$type];
            foreach ($sub as $item) {
                $subDocument = $this->getSearchableDocument($typeFields, $item);

                if (!$field->multiple) {
                    return [$type => $subDocument];
                }

                $documents[$type][] = $subDocument;
            }
        }

        return $documents;
    }
}
