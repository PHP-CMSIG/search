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

namespace CmsIg\Seal\Tests;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\Reindex\DynamicReindexProviderInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Task\SyncTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Engine::class)]
final class EngineReindexTest extends TestCase
{
    /**
     * @return iterable<string, array{string|null, bool, list<string>}>
     */
    public static function provideSelections(): iterable
    {
        yield 'all' => [null, true, ['blog', 'news']];
        yield 'blog only' => ['blog', true, ['blog']];
        yield 'news only' => ['news', true, ['news']];
        yield 'unknown' => ['missing', true, []];
        yield 'drop disabled' => [null, false, []];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideSelections')]
    public function testOnlySelectedIndexesAreDropped(string|null $selection, bool $dropIndex, array $expected): void
    {
        $schema = new Schema([
            'blog' => new Index('blog', ['id' => new IdentifierField('id')]),
            'news' => new Index('news', ['id' => new IdentifierField('id')]),
        ]);
        $dropped = $created = [];
        $manager = $this->createMock(SchemaManagerInterface::class);
        $manager->method('existIndex')->willReturn(true);
        $manager->method('dropIndex')->willReturnCallback(static function (Index $index) use (&$dropped) {
            $dropped[] = $index->name;

            return new SyncTask(null);
        });
        $manager->method('createIndex')->willReturnCallback(static function (Index $index) use (&$created) {
            $created[] = $index->name;

            return new SyncTask(null);
        });
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('getSchemaManager')->willReturn($manager);
        $adapter->expects(self::never())->method('getIndexer');
        $provider = $this->createMock(DynamicReindexProviderInterface::class);
        // Empty selected indexes still need to be cleared when dropping is requested.
        $provider->method('total')->willReturn(0);
        $provider->expects(self::never())->method('provide');
        (new Engine($adapter, $schema))->reindex([$provider], ReindexConfig::create()->withIndex($selection)->withDropIndex($dropIndex));
        self::assertSame($expected, $dropped);
        self::assertSame($expected, $created);
    }
}
