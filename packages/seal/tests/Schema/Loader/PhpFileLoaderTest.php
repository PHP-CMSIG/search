<?php

declare(strict_types=1);

/*
 * This file is part of the Schranz Search package.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Schranz\Search\SEAL\Tests\Schema\Loader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Schranz\Search\SEAL\Schema\Field;
use Schranz\Search\SEAL\Schema\Loader\PhpFileLoader;

#[CoversClass(PhpFileLoader::class)]
class PhpFileLoaderTest extends TestCase
{
    public function testLoadBasic(): void
    {
        $schema = (new PhpFileLoader([__DIR__ . '/fixtures/basic']))->load();

        $this->assertEqualsCanonicalizing(
            [
                'news',
                'blog',
            ],
            \array_keys($schema->indexes),
        );
    }

    public function testLoadMerge(): void
    {
        $schema = (new PhpFileLoader([__DIR__ . '/fixtures/merge']))->load();

        $this->assertSame(['blog'], \array_keys($schema->indexes));

        $this->assertSame(
            [
                'id',
                'title',
                'description',
                'blocks',
                'footerText',
            ],
            \array_keys($schema->indexes['blog']->fields),
        );

        $this->assertSame(
            [
                'option1' => true,
                'option2' => true,
            ],
            $schema->indexes['blog']->fields['description']->options,
        );

        $this->assertInstanceOf(Field\TypedField::class, $schema->indexes['blog']->fields['blocks']);

        $this->assertSame(
            [
                'text',
                'embed',
                'gallery',
            ],
            \array_keys($schema->indexes['blog']->fields['blocks']->types),
        );

        $this->assertTrue(
            $schema->indexes['blog']->fields['blocks']->types['gallery']['media']->multiple,
        );
    }

    public function testMergeWithPrefix(): void
    {
        $schema = (new PhpFileLoader([__DIR__ . '/fixtures/merge'], 'prefix_'))->load();

        $this->assertSame(['blog'], \array_keys($schema->indexes));

        $this->assertSame('prefix_blog', $schema->indexes['blog']->name);
    }

    public function testLoadBasicWithPrefix(): void
    {
        $schema = (new PhpFileLoader([__DIR__ . '/fixtures/basic'], 'prefix_'))->load();

        $this->assertEqualsCanonicalizing(
            [
                'news',
                'blog',
            ],
            \array_keys($schema->indexes),
        );

        $this->assertSame('prefix_blog', $schema->indexes['blog']->name);
        $this->assertSame('prefix_news', $schema->indexes['news']->name);
    }
}
