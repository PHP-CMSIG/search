<?php

namespace CmsIg\Seal\Odm\Reindex;

use CmsIg\Seal\Odm\Mapper\OdmDataMapperInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;

/**
 * @internal This class is only intended to be used internally by the Seal ODM package.
 */
final class OdmDataMapperReindexProvider implements ReindexProviderInterface
{
    public function __construct(
        private readonly OdmReindexProviderInterface $provider,
        private readonly OdmDataMapperInterface $dataMapper,
    ) {
    }

    public function total(): int|null
    {
        return $this->provider->total();
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        foreach ($this->provider->provide($reindexConfig) as $object) {
            yield $this->dataMapper->objectToArray($this->provider->getIndex(), $object);
        }
    }

    public function getIndexName(): string
    {
        return $this->provider->getIndexName();
    }
}
