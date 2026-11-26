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

use CmsIg\Seal\Adapter\Loupe\LoupeHelper;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\LoupeFactory;
use PHPUnit\Framework\TestCase;

class LoupeHelperTest extends TestCase
{
    public function testLanguagesFromIndexOptions(): void
    {
        $directory = \sys_get_temp_dir() . '/seal-loupe-' . \uniqid();
        $helper = new LoupeHelper(new LoupeFactory(), $directory);
        $index = new Index('languages', [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
        ], options: [
            'loupe' => [
                'languages' => ['de', 'en'],
            ],
        ]);

        $helper->createIndex($index);

        /** @var Configuration $configuration */
        $configuration = \unserialize((string) \file_get_contents($directory . '/languages/config.loupe'));

        $helper->dropIndex($index);

        $this->assertSame(['de', 'en'], $configuration->getLanguages());
    }
}
