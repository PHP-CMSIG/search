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

namespace CmsIg\Seal\Adapter\MongoDB\Tests;

use CmsIg\Seal\Adapter\MongoDB\ClientWrapper;
use CmsIg\Seal\Adapter\MongoDB\MongoDBSchemaManager;
use CmsIg\Seal\Testing\AbstractSchemaManagerTestCase;

class MongoDBSchemaManagerTest extends AbstractSchemaManagerTestCase
{
    private static ClientWrapper $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = ClientHelper::getClient();
        self::$schemaManager = new MongoDBSchemaManager(self::$client);

        parent::setUpBeforeClass();
    }
}
