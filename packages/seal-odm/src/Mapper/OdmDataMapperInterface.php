<?php

namespace CmsIg\Seal\Odm\Mapper;

interface OdmDataMapperInterface
{
    /**
     * @return array<string, mixed>
     */
    public function objectToArray(string $index, object $object): array;

    /**
     * @param array<string, mixed> $document
     */
    public function arrayToObject(string $index, array $document): object;
}
