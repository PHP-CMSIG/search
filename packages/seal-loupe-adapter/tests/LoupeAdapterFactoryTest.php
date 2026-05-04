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

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use Loupe\Loupe\Configuration;
use PHPUnit\Framework\TestCase;

class LoupeAdapterFactoryTest extends TestCase
{
    public function testCreateHelperWithConfigurationString(): void
    {
        $directory = \sys_get_temp_dir() . '/seal-loupe-adapter-factory-test-' . \uniqid('', true);
        \mkdir($directory, 0777, true);

        $configuration = Configuration::create()->withMaxTotalHits(42);
        $dsn = 'loupe://' . $directory . '?configuration=' . \rawurlencode($configuration->toString());

        $loupeAdapterFactory = new LoupeAdapterFactory();
        $factory = new AdapterFactory([
            'loupe' => $loupeAdapterFactory,
        ]);

        $parsedDsn = $factory->parseDsn($dsn);
        $helper = $loupeAdapterFactory->createHelper($parsedDsn);

        $index = new Index('test', [
            'id' => new IdentifierField('id'),
            'title' => new TextField('title'),
        ]);

        $helper->createIndex($index);

        self::assertSame(42, $helper->getLoupe($index)->getConfiguration()->getMaxTotalHits());

        $helper->dropIndex($index);
        \rmdir($directory);
    }

    public function testCreateHelperWithInvalidConfigurationString(): void
    {
        $loupeAdapterFactory = new LoupeAdapterFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "configuration" query param must contain a Loupe\\Loupe\\Configuration string.');

        $loupeAdapterFactory->createHelper([
            'host' => '',
            'query' => [
                'configuration' => 'not-json',
            ],
        ]);
    }
}
