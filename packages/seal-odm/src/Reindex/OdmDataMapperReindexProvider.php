<?php

namespace CmsIg\Seal\Odm\Reindex;

use CmsIg\Seal\Odm\Mapper\OdmDataMapperInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\DynamicReindexProviderInterface;

/**
 * @internal This class is only intended to be used internally by the Seal ODM package.
 */
final class OdmDataMapperReindexProvider implements DynamicReindexProviderInterface
{
    /**
     * @var iterable<OdmStaticReindexProviderInterface> $providers
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
            if (\is_null($newTotal)) {
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

            foreach ($this->provider->provide($reindexConfig) as $object) {
                yield $this->dataMapper->objectToArray($index, $object);
            }
        }
    }
}
