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

namespace CmsIg\Seal\Tests\Schema\Loader;

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Loader\LoaderInterface;
use CmsIg\Seal\Schema\Loader\PhpFileLoader;
use CmsIg\Seal\Schema\Loader\PrefixLoader;
use CmsIg\Seal\Schema\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrefixLoader::class)]
class PrefixLoaderTest extends TestCase
{
    public function testPrefixPreservesKeysAndSchemaContents(): void
    {
        $source = new Schema([
            'logical' => new Index('physical', ['id' => new Field\IdentifierField('id')], ['nested' => ['keep' => true]]),
            'blog' => new Index('prefix_blog', []),
        ]);
        $inner = $this->createMock(LoaderInterface::class);
        $inner->expects($this->exactly(2))->method('load')->willReturn($source);
        $loader = new PrefixLoader($inner, 'prefix_');

        $schema = $loader->load();
        $again = $loader->load();

        $this->assertSame(['logical', 'blog'], \array_keys($schema->indexes));
        $this->assertSame('prefix_physical', $schema->indexes['logical']->name);
        $this->assertSame('prefix_prefix_blog', $schema->indexes['blog']->name);
        $this->assertSame($source->indexes['logical']->fields, $schema->indexes['logical']->fields);
        $this->assertSame($source->indexes['logical']->options, $schema->indexes['logical']->options);
        $this->assertNotSame($source, $schema);
        $this->assertNotSame($source->indexes['logical'], $schema->indexes['logical']);
        $this->assertSame('physical', $source->indexes['logical']->name);
        $this->assertEqualsCanonicalizing($schema, $again);
    }

    public function testEmptyPrefix(): void
    {
        $source = new Schema(['blog' => new Index('blog', [])]);
        $inner = $this->createMock(LoaderInterface::class);
        $inner->expects($this->once())->method('load')->willReturn($source);

        $this->assertEqualsCanonicalizing($source, (new PrefixLoader($inner, ''))->load());
    }

    public function testEmptySchema(): void
    {
        $inner = $this->createMock(LoaderInterface::class);
        $inner->expects($this->once())->method('load')->willReturn(new Schema([]));

        $this->assertSame([], (new PrefixLoader($inner, 'prefix_'))->load()->indexes);
    }

    public function testLoadBasicWithPrefix(): void
    {
        $inner = new PhpFileLoader([__DIR__ . '/fixtures/basic']);
        $original = $inner->load();
        $schema = (new PrefixLoader($inner, 'prefix_'))->load();

        $this->assertSame(\array_keys($original->indexes), \array_keys($schema->indexes));
        $this->assertSame('prefix_blog', $schema->indexes['blog']->name);
        $this->assertSame('prefix_news', $schema->indexes['news']->name);
    }

    public function testMergeWithPrefix(): void
    {
        $inner = new PhpFileLoader([__DIR__ . '/fixtures/merge']);
        $original = $inner->load();
        $schema = (new PrefixLoader($inner, 'prefix_'))->load();

        $this->assertSame(['blog'], \array_keys($schema->indexes));
        $this->assertSame('prefix_blog', $schema->indexes['blog']->name);
        $this->assertEqualsCanonicalizing($original->indexes['blog']->fields, $schema->indexes['blog']->fields);
        $this->assertSame($original->indexes['blog']->options, $schema->indexes['blog']->options);
    }
}
