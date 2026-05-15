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

namespace CmsIg\Seal\Odm\Reindex;

use CmsIg\Seal\Odm\Mapper\OdmDataMapperInterface;
use CmsIg\Seal\Reindex\DynamicReindexProviderInterface;
use CmsIg\Seal\Reindex\ReindexConfig;

/**
 * @internal this class is only intended to be used internally by the Seal ODM package
 */
final class OdmDataMapperReindexProvider implements DynamicReindexProviderInterface
{
    /**
     * @param iterable<OdmStaticReindexProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly OdmDataMapperInterface $dataMapper,
    ) {
    }

    public function total(string $index): int|null
    {
        $total = 0;
        foreach ($this->providers as $provider) {
            if ($index !== $provider->getIndexName()) {
                continue;
            }

            $newTotal = $provider->total();
            if (null === $newTotal) {
                return null;
            }

            $total += $newTotal;
        }

        return $total;
    }

    public function provide(string $index, ReindexConfig $reindexConfig): \Generator
    {
        foreach ($this->providers as $provider) {
            if ($index !== $provider->getIndexName()) {
                continue;
            }

            foreach ($provider->provide($reindexConfig) as $object) {
                yield $this->dataMapper->objectToArray($index, $object);
            }
        }
    }
}
