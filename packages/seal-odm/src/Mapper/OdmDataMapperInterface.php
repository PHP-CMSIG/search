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
