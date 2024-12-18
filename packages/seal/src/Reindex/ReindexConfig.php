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

final class ReindexConfig
{
    private string|null $index = null;
    private bool $dropIndex = false;
    private int $bulkSize = 100;
    private \DateTimeInterface|null $dateTimeBoundary = null;

    /**
     * @var array<string>
     */
    private array $identifiers = [];

    public function getIndex(): string|null
    {
        return $this->index;
    }

    public function shouldDropIndex(): bool
    {
        return $this->dropIndex;
    }

    public function getBulkSize(): int
    {
        return $this->bulkSize;
    }

    public function getDateTimeBoundary(): \DateTimeInterface|null
    {
        return $this->dateTimeBoundary;
    }

    /**
     * @return array<string>
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    public function withDateTimeBoundary(\DateTimeInterface|null $dateTimeBoundary): self
    {
        $clone = clone $this;
        $clone->dateTimeBoundary = $dateTimeBoundary;

        return $clone;
    }

    /**
     * @param array<string> $identifiers
     */
    public function withIdentifiers(array $identifiers): self
    {
        $clone = clone $this;
        $clone->identifiers = $identifiers;

        return $clone;
    }

    public function withBulkSize(int $bulkSize): self
    {
        $clone = clone $this;
        $clone->bulkSize = $bulkSize;

        return $clone;
    }

    public function withIndex(string|null $index): self
    {
        $clone = clone $this;
        $clone->index = $index;

        return $clone;
    }

    public function withDropIndex(bool $dropIndex): self
    {
        $clone = clone $this;
        $clone->dropIndex = $dropIndex;

        return $clone;
    }

    public static function create(): self
    {
        return new self();
    }
}
