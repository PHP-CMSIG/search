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

namespace CmsIg\Seal\Adapter\Loupe\Tests;

use CmsIg\Seal\Adapter\Loupe\LoupeAdapter;
use CmsIg\Seal\Testing\AbstractSearcherTestCase;
use Composer\InstalledVersions;

class LoupeSearcherTest extends AbstractSearcherTestCase
{
    public static function setUpBeforeClass(): void
    {
        $helper = ClientHelper::getHelper();
        self::$adapter = new LoupeAdapter($helper);

        parent::setUpBeforeClass();
    }

    public function testZeroLimitWithFacets(): void
    {
        // Loupe below 1.1.4 returned empty facets when the limit was zero
        if (\version_compare((string) InstalledVersions::getVersion('loupe/loupe'), '1.1.4.0', '<')) {
            $this->markTestSkipped('Facets with a zero limit require loupe/loupe 1.1.4 or newer.');
        }

        parent::testZeroLimitWithFacets();
    }
}
