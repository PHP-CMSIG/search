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

use CmsIg\Seal\Adapter\MongoDB\MongoDBAdapter;
use CmsIg\Seal\Testing\AbstractAdapterTestCase;

class MongoDBAdapterTest extends AbstractAdapterTestCase
{
    public static function setUpBeforeClass(): void
    {
        $client = ClientHelper::getClient();
        self::$adapter = new MongoDBAdapter($client);

        parent::setUpBeforeClass();
    }
}
