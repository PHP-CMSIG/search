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
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\StaticReindexProviderInterface;

/**
 * @internal this class is only intended to be used internally by the Seal ODM package
 */
final class OdmDataMapperReindexProvider implements StaticReindexProviderInterface
{
    public function __construct(
        private readonly OdmStaticReindexProviderInterface $provider,
        private readonly OdmDataMapperInterface $dataMapper,
    ) {
    }

    public function getIndexName(): string
    {
        return $this->provider->getIndexName();
    }

    public function total(): int|null
    {
        return $this->provider->total();
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        foreach ($this->provider->provide($reindexConfig) as $object) {
            yield $this->dataMapper->objectToArray($this->getIndexName(), $object);
        }
    }
}
