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

namespace CmsIg\Seal\Reindex;

interface DynamicReindexProviderInterface
{
    /**
     * Returns how many documents this provider will provide. Returns `null` if the total is unknown.
     * This method should return 0 if the index is not supported or no documents exists for the given index.
     */
    public function total(string $index): int|null;

    /**
     * The reindex provider returns a Generator which provides the documents to reindex for the given index.
     * Early return; if the provider does not support the given index.
     *
     * @return \Generator<array<string, mixed>>
     */
    public function provide(string $index, ReindexConfig $reindexConfig): \Generator;
}
