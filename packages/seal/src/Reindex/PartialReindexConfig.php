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
    public function __construct(private \DateTimeInterface|\Closure $dateTimeBoundary, private \Generator|\Closure $identifiers)
    {
    }

    public function getDateTimeBoundary(): \DateTimeInterface
    {
        $this->resolveIfClosure($this->dateTimeBoundary);

        return $this->dateTimeBoundary;
    }

    public function getIdentifiers(): \Generator
    {
        $this->resolveIfClosure($this->identifiers);

        return $this->identifiers;
    }

    public static function createConditional(\DateTimeInterface|\Closure|null $dateTimeBoundary, \Generator|\Closure|null $identifiers): self|null
    {
        if (null === $dateTimeBoundary && null === $identifiers) {
            return null;
        }

        return new self($dateTimeBoundary, $identifiers);
    }

    public static function createGeneratorFromArray(array $array): \Generator
    {
        foreach ($array as $value) {
            yield $value;
        }
    }

    private function resolveIfClosure(mixed &$property): void
    {
        if ($property instanceof \Closure) {
            $property = $property($this);
        }
    }
}
