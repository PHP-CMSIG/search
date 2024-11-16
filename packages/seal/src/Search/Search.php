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

use CmsIg\Seal\Schema\Index;

final class Search
{
    /**
     * @param object[] $filters
     * @param array<string, 'asc'|'desc'> $sortBys
     */
    public function __construct(
        public readonly Index $index,
        public readonly array $filters = [],
        public readonly array $sortBys = [],
        public readonly int|null $limit = null,
        public readonly int $offset = 0,
    ) {
    }
}
