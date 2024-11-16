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

/**
 * @extends \IteratorIterator<int, array<string, mixed>, \Generator>
 */
class Result extends \IteratorIterator
{
    /**
     * @param \Generator<int, array<string, mixed>> $documents
     */
    public function __construct(
        \Generator $documents,
        readonly private int $total,
    ) {
        parent::__construct($documents);
    }

    public function total(): int
    {
        return $this->total;
    }
}
