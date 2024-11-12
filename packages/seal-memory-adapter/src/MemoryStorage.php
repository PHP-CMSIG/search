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

use CmsIg\Seal\Schema\Index;

/**
 * @internal this class is not part of the public API and may change or remove without notice
 */
final class MemoryStorage
{
    /**
     * @var Index[]
     */
    private static array $indexes = [];

    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private static array $documents = [];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getDocuments(Index $index): array
    {
        if (!\array_key_exists($index->name, self::$indexes)) {
            throw new \RuntimeException('Index "' . $index->name . '" does not exist.');
        }

        return self::$documents[$index->name];
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public static function save(Index $index, array $document): array
    {
        if (!\array_key_exists($index->name, self::$indexes)) {
            throw new \RuntimeException('Index "' . $index->name . '" does not exist.');
        }

        $identifierField = $index->getIdentifierField();

        /** @var string|int $identifier */
        $identifier = $document[$identifierField->name];

        self::$documents[$index->name][(string) $identifier] = $document;

        return $document;
    }

    public static function delete(Index $index, string $identifier): void
    {
        if (!\array_key_exists($index->name, self::$indexes)) {
            throw new \RuntimeException('Index "' . $index->name . '" does not exist.');
        }

        unset(self::$documents[$index->name][$identifier]);
    }

    public static function dropIndex(Index $index): void
    {
        unset(self::$indexes[$index->name]);
        unset(self::$documents[$index->name]);
    }

    public static function createIndex(Index $index): void
    {
        self::$indexes[$index->name] = $index;
        self::$documents[$index->name] = [];
    }

    public static function existIndex(Index $index): bool
    {
        return \array_key_exists($index->name, self::$indexes);
    }
}
