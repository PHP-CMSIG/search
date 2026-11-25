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
use CmsIg\Seal\Schema\Loader\PhpFileLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
        $this->assertSame('blog', $schema->indexes['blog']->name);
        $this->assertSame('news', $schema->indexes['news']->name);
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
                'rating',
                'price',
                'created',
                'published',
                'archived',
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

        $this->assertSame([
            'keepOption' => 'keepValue',
            'simpleOption' => 'simpleOption-overwrite',
            'nestedOption' => [
                'key1' => 'value1-overwrite',
                'key2' => 'value2',
                'key3' => 'value3-new',
            ],
        ], $schema->indexes['blog']->options);

        $index = $schema->indexes['blog'];
        $flaggedFields = ['description', 'blocks.text.description', 'rating', 'price', 'created', 'published'];
        $this->assertSame($flaggedFields, $index->distinctFields);
        $this->assertSame($flaggedFields, $index->facetFields);

        $published = $index->fields['published'];
        $archived = $index->fields['archived'];
        $this->assertInstanceOf(Field\BooleanField::class, $published);
        $this->assertInstanceOf(Field\BooleanField::class, $archived);
        $this->assertTrue($published->multiple);
        $this->assertTrue($published->filterable);
        $this->assertTrue($published->sortable);
        $this->assertSame(['nested' => ['keep' => true, 'replace' => 'new']], $published->options);
        $this->assertFalse($archived->multiple);
        $this->assertFalse($archived->filterable);
        $this->assertFalse($archived->sortable);
    }
}
