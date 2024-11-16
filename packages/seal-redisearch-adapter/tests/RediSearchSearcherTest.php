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

namespace CmsIg\Seal\Adapter\RediSearch\Tests;

use CmsIg\Seal\Adapter\RediSearch\RediSearchAdapter;
use CmsIg\Seal\Testing\AbstractSearcherTestCase;

class RediSearchSearcherTest extends AbstractSearcherTestCase
{
    public static function setUpBeforeClass(): void
    {
        $client = ClientHelper::getClient();
        self::$adapter = new RediSearchAdapter($client);

        parent::setUpBeforeClass();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testGeoBoundingBoxCondition(): void
    {
        $this->markTestSkipped('Not supported by RediSearch: https://github.com/RediSearch/RediSearch/issues/680 or https://github.com/RediSearch/RediSearch/issues/5032');
    }
}
