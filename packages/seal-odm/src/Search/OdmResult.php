<?php

namespace CmsIg\Seal\Odm\Search;

final class OdmResult extends \IteratorIterator
{
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
