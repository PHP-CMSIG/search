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

namespace CmsIg\Seal\Adapter\ReadWrite;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;

final class ReadWriteAdapter implements AdapterInterface
{
    public function __construct(
        private readonly AdapterInterface $readAdapter,
        private readonly AdapterInterface $writeAdapter,
    ) {
    }

    public function getSchemaManager(): SchemaManagerInterface
    {
        return $this->writeAdapter->getSchemaManager();
    }

    public function getIndexer(): IndexerInterface
    {
        return $this->writeAdapter->getIndexer();
    }

    public function getSearcher(): SearcherInterface
    {
        return $this->readAdapter->getSearcher();
    }
}
