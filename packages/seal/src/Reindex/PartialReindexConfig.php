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

final class PartialReindexConfig
{
    public function __construct(private readonly \DateTimeInterface|null $dateTimeBoundary = null, private readonly array $identifiers = [])
    {
    }

    public function getDateTimeBoundary(): \DateTimeInterface|null
    {
        return $this->dateTimeBoundary;
    }

    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    public static function createConditional(\DateTimeInterface|null $dateTimeBoundary, array|null $identifiers): self|null
    {
        if (!$dateTimeBoundary instanceof \DateTimeInterface && null === $identifiers) {
            return null;
        }

        return new self($dateTimeBoundary, $identifiers);
    }
}
