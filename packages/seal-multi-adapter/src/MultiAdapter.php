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

namespace CmsIg\Seal\Adapter\Multi;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;

final class MultiAdapter implements AdapterInterface
{
    private SchemaManagerInterface|null $schemaManager = null;

    private IndexerInterface|null $indexer = null;

    private SearcherInterface $searcher;

    /**
     * @param iterable<AdapterInterface> $adapters
     */
    public function __construct(
        private readonly iterable $adapters,
    ) {
    }

    public function getSchemaManager(): SchemaManagerInterface
    {
        if (!$this->schemaManager instanceof SchemaManagerInterface) {
            $schemaManagers = [];
            foreach ($this->adapters as $adapter) {
                $schemaManagers[] = $adapter->getSchemaManager();
            }

            $this->schemaManager = new MultiSchemaManager($schemaManagers);
        }

        return $this->schemaManager;
    }

    public function getIndexer(): IndexerInterface
    {
        if (!$this->indexer instanceof IndexerInterface) {
            $indexers = [];
            foreach ($this->adapters as $adapter) {
                $indexers[] = $adapter->getIndexer();
            }

            $this->indexer = new MultiIndexer($indexers);
        }

        return $this->indexer;
    }

    public function getSearcher(): SearcherInterface
    {
        if (!$this->indexer instanceof IndexerInterface) {
            $searchers = [];
            foreach ($this->adapters as $adapter) {
                $searchers[] = $adapter->getSearcher();
            }

            $this->searcher = new MultiSearcher($searchers);
        }

        return $this->searcher;
    }
}
