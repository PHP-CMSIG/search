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

namespace CmsIg\Seal\Adapter\Elasticsearch;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use Elastic\Elasticsearch\Client;

final class ElasticsearchAdapter implements AdapterInterface
{
    private readonly SchemaManagerInterface $schemaManager;

    private readonly IndexerInterface $indexer;

    private readonly SearcherInterface $searcher;

    public function __construct(
        Client $client,
        SchemaManagerInterface|null $schemaManager = null,
        IndexerInterface|null $indexer = null,
        SearcherInterface|null $searcher = null,
    ) {
        if ($client->getAsync()) {
            throw new \RuntimeException('Currently only synchronous client is supported.');
        }

        $this->schemaManager = $schemaManager ?? new ElasticsearchSchemaManager($client);
        $this->indexer = $indexer ?? new ElasticsearchIndexer($client);
        $this->searcher = $searcher ?? new ElasticsearchSearcher($client);
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
}
