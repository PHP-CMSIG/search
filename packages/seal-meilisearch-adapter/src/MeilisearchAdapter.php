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

namespace CmsIg\Seal\Adapter\Meilisearch;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Adapter\StatisticsInterface;
use Meilisearch\Client;

final class MeilisearchAdapter implements AdapterInterface
{
    private readonly SchemaManagerInterface $schemaManager;

    private readonly IndexerInterface $indexer;

    private readonly SearcherInterface $searcher;

    private readonly StatisticsInterface $statistics;

    public function __construct(
        Client $client,
        SchemaManagerInterface|null $schemaManager = null,
        IndexerInterface|null $indexer = null,
        SearcherInterface|null $searcher = null,
        StatisticsInterface|null $statistics = null,
    ) {
        $this->schemaManager = $schemaManager ?? new MeilisearchSchemaManager($client);
        $this->indexer = $indexer ?? new MeilisearchIndexer($client);
        $this->searcher = $searcher ?? new MeilisearchSearcher($client);
        $this->statistics = $statistics ?? new MeilisearchStatistics($client);
    }

    public function getSchemaManager(): SchemaManagerInterface
    {
        return $this->schemaManager;
    }

    public function getIndexer(): IndexerInterface
    {
        return $this->indexer;
    }

    public function getSearcher(): SearcherInterface
    {
        return $this->searcher;
    }

    public function getStatistics(): StatisticsInterface
    {
        return $this->statistics;
    }
}
