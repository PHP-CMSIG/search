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
final class Result extends \IteratorIterator
{
    /**
     * @param \Generator<int, array<string, mixed>> $documents
     * @param array<string, mixed> $facets
     */
    public function __construct(
        \Generator $documents,
        private readonly int $total,
        private readonly array $facets = [],
    ) {
        parent::__construct($documents);
    }

    public function total(): int
    {
        return $this->total;
    }

    /**
     * @return array<string, mixed>
     */
    public function facets(): array
    {
        return $this->facets;
    }

    public static function createEmpty(): static
    {
        return new self((static function (): \Generator {
            yield from [];
        })(), 0);
    }
}
