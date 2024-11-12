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

namespace CmsIg\Seal\Adapter\Memory\Tests;

use CmsIg\Seal\Adapter\Memory\MemoryAdapter;
use CmsIg\Seal\Testing\AbstractAdapterTestCase;

class MemoryAdapterTest extends AbstractAdapterTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$adapter = new MemoryAdapter();

        parent::setUpBeforeClass();
    }
}
