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

        $this->assertSame([
            'keepOption' => 'keepValue',
            'simpleOption' => 'simpleOption-overwrite',
            'nestedOption' => [
                'key1' => 'value1-overwrite',
                'key2' => 'value2',
                'key3' => 'value3-new',
            ],
        ], $schema->indexes['blog']->options);
    }

    public function testMergePreservesDistinctAndFacetFlags(): void
    {
        $index = (new PhpFileLoader([__DIR__ . '/fixtures/merge-field-flags']))->load()->indexes['flags'];

        foreach (['text', 'integer', 'float', 'dateTime'] as $name) {
            $this->assertTrue($index->fields[$name . 'Enabled']->distinct);
            $this->assertTrue($index->fields[$name . 'Enabled']->facet);
            $this->assertFalse($index->fields[$name . 'Disabled']->distinct);
            $this->assertFalse($index->fields[$name . 'Disabled']->facet);
            $this->assertSame(['nested' => ['keep' => true, 'replace' => 'new']], $index->fields[$name . 'Enabled']->options);
            $this->assertContains($name . 'Enabled', $index->distinctFields);
            $this->assertContains($name . 'Enabled', $index->facetFields);
            $this->assertNotContains($name . 'Disabled', $index->distinctFields);
            $this->assertNotContains($name . 'Disabled', $index->facetFields);
        }

        foreach (['object.value', 'typed.text.value'] as $path) {
            $this->assertTrue($index->getFieldByPath($path)->distinct);
            $this->assertTrue($index->getFieldByPath($path)->facet);
            $this->assertContains($path, $index->distinctFields);
            $this->assertContains($path, $index->facetFields);
        }
    }

    public function testMergeBooleanFields(): void
    {
        $index = (new PhpFileLoader([__DIR__ . '/fixtures/merge-boolean']))->load()->indexes['boolean'];
        $enabled = $index->fields['enabled'];
        $disabled = $index->fields['disabled'];

        $this->assertInstanceOf(Field\BooleanField::class, $enabled);
        $this->assertInstanceOf(Field\BooleanField::class, $disabled);
        $this->assertTrue($enabled->multiple);
        $this->assertTrue($enabled->filterable);
        $this->assertTrue($enabled->sortable);
        $this->assertTrue($enabled->distinct);
        $this->assertTrue($enabled->facet);
        $this->assertSame(['nested' => ['keep' => true, 'replace' => 'new']], $enabled->options);
        $this->assertFalse($disabled->multiple);
        $this->assertFalse($disabled->filterable);
        $this->assertFalse($disabled->sortable);
        $this->assertFalse($disabled->distinct);
        $this->assertFalse($disabled->facet);
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
