<?php

namespace Schranz\Search\SEAL\Schema;

final class Index
{
    /**
     * @param array<string, AbstractField> $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly string $normalizedName,
        public readonly array $fields
    ) {}
}
