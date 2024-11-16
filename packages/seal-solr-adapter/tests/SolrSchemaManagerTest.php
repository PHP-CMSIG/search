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

namespace CmsIg\Seal\Adapter\Solr\Tests;

use CmsIg\Seal\Adapter\Solr\SolrSchemaManager;
use CmsIg\Seal\Testing\AbstractSchemaManagerTestCase;

class SolrSchemaManagerTest extends AbstractSchemaManagerTestCase
{
    public static function setUpBeforeClass(): void
    {
        $client = ClientHelper::getClient();
        self::$schemaManager = new SolrSchemaManager($client);
    }
}
