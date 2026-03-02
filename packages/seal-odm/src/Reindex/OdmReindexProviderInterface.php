<?php

namespace CmsIg\Seal\Odm\Reindex;

use CmsIg\Seal\Reindex\ReindexConfig;

interface OdmReindexProviderInterface
{
    /**
     * Returns how many documents this provider will provide. Returns `null` if the total is unknown.
     */
    public function total(): int|null;

    /**
     * The reindex provider returns a Generator which provides the objects to reindex.
     *
     * @return \Generator<object>
     */
    public function provide(ReindexConfig $reindexConfig): \Generator;

    /**
     * The name of the index for which the objects are for.
     */
    public function getIndexName(): string;
}
