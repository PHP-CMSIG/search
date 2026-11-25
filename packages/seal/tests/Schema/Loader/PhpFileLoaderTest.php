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
use CmsIg\Seal\Schema\Loader\PrefixLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpFileLoader::class)]
class PhpFileLoaderTest extends TestCase
{
    #[DataProvider('provideLegacyPrefixes')]
    public function testLegacyPrefix(string $fixture, string $prefix, bool $named): void
    {
        $directories = [__DIR__ . '/fixtures/' . $fixture];
        $deprecations = [];
        \set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool {
            $deprecations[] = [$severity, $message];

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $loader = $named
                ? new PhpFileLoader(directories: $directories, indexNamePrefix: $prefix)
                : new PhpFileLoader($directories, $prefix);

            $this->assertCount(1, $deprecations);
            $schema = $loader->load();
            $again = $loader->load();
            $expected = (new PrefixLoader(new PhpFileLoader($directories), $prefix))->load();

            $this->assertSame(\array_keys($expected->indexes), \array_keys($schema->indexes));
            $this->assertEqualsCanonicalizing($expected, $schema);
            $this->assertEqualsCanonicalizing($schema, $again);
            $this->assertSame([[
                \E_USER_DEPRECATED,
                'Passing a non-empty $indexNamePrefix to PhpFileLoader is deprecated and will no longer be supported in 0.13. Use new PrefixLoader(new PhpFileLoader($directories), $indexNamePrefix) instead.',
            ]], $deprecations);
        } finally {
            \restore_error_handler();
        }
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function provideLegacyPrefixes(): iterable
    {
        yield 'basic positional' => ['basic', 'prefix_', false];
        yield 'basic named' => ['basic', 'prefix_', true];
        yield 'merged positional' => ['merge', 'prefix_', false];
        yield 'merged named' => ['merge', 'prefix_', true];
        yield 'zero string is non-empty' => ['basic', '0', false];
    }

    public function testNoDeprecationWithoutPrefix(): void
    {
        $deprecations = [];
        \set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool {
            $deprecations[] = [$severity, $message];

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $directories = [__DIR__ . '/fixtures/basic'];
            $schema = (new PhpFileLoader($directories))->load();
            $emptyPrefix = (new PhpFileLoader($directories, ''))->load();
            $namedEmptyPrefix = (new PhpFileLoader(directories: $directories, indexNamePrefix: ''))->load();
            $decorated = (new PrefixLoader(new PhpFileLoader($directories), 'prefix_'))->load();

            $this->assertSame('blog', $schema->indexes['blog']->name);
            $this->assertEqualsCanonicalizing($schema, $emptyPrefix);
            $this->assertEqualsCanonicalizing($schema, $namedEmptyPrefix);
            $this->assertSame('prefix_blog', $decorated->indexes['blog']->name);
            $this->assertSame([], $deprecations);
        } finally {
            \restore_error_handler();
        }
    }

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
