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

namespace CmsIg\Seal\Adapter;

/**
 * @internal
 */
final class BulkHelper
{
    /**
     * @template T of mixed
     *
     * @param iterable<T> $iterables
     *
     * @return \Generator<T[]>
     */
    public static function splitBulk(iterable $iterables, int $bulkSize): \Generator
    {
        $bulk = [];
        $count = 0;

        foreach ($iterables as $iterable) {
            $bulk[] = $iterable;
            ++$count;

            if (0 === ($count % $bulkSize)) {
                yield $bulk;
                $bulk = [];
            }
        }

        if ([] !== $bulk) {
            yield $bulk;
        }
    }
}
