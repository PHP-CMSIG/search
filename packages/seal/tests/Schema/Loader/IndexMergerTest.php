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
use CmsIg\Seal\Schema\Loader\IndexMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexMerger::class)]
class IndexMergerTest extends TestCase
{
    public function testMergeIndexes(): void
    {
        $merger = new IndexMerger();
        $original = new Index('blog', ['id' => new Field\IdentifierField('id')], [
            'keep' => true,
            'nested' => ['keep' => true, 'replace' => 'old'],
        ]);
        $indexes = $merger->mergeIndexes([], $original);
        $indexes = $merger->mergeIndexes($indexes, new Index('news', []));
        $indexes = $merger->mergeIndexes($indexes, new Index('blog', ['title' => new Field\TextField('title')], [
            'nested' => ['replace' => 'new', 'add' => true],
        ]));

        $this->assertSame(['blog', 'news'], \array_keys($indexes));
        $this->assertSame('blog', $indexes['blog']->name);
        $this->assertSame(['id', 'title'], \array_keys($indexes['blog']->fields));
        $this->assertSame([
            'keep' => true,
            'nested' => ['keep' => true, 'replace' => 'new', 'add' => true],
        ], $indexes['blog']->options);
        $this->assertSame(['id'], \array_keys($original->fields));
        $this->assertSame([
            'keep' => true,
            'nested' => ['keep' => true, 'replace' => 'old'],
        ], $original->options);
    }

    #[DataProvider('provideScalarFields')]
    public function testMergeScalarFields(Field\AbstractField $old, Field\AbstractField $new): void
    {
        $merged = (new IndexMerger())->mergeFields(['value' => $old], ['value' => $new])['value'];

        $this->assertSame($new::class, $merged::class);
        $this->assertSame($new->name, $merged->name);
        $this->assertSame($new->multiple, $merged->multiple);
        $this->assertSame($new->searchable, $merged->searchable);
        $this->assertSame($new->filterable, $merged->filterable);
        $this->assertSame($new->sortable, $merged->sortable);
        $this->assertSame($new->distinct, $merged->distinct);
        $this->assertSame($new->facet, $merged->facet);
        $this->assertSame(['nested' => ['keep' => true, 'replace' => 'new']], $merged->options);
    }

    /**
     * @return iterable<string, array{Field\AbstractField, Field\AbstractField}>
     */
    public static function provideScalarFields(): iterable
    {
        foreach ([Field\TextField::class, Field\IntegerField::class, Field\FloatField::class, Field\BooleanField::class, Field\DateTimeField::class] as $class) {
            yield $class => [
                new $class('value', options: ['nested' => ['keep' => true, 'replace' => 'old']]),
                new $class('value', multiple: true, searchable: false, filterable: true, sortable: true, distinct: true, facet: true, options: ['nested' => ['replace' => 'new']]),
            ];
        }
    }

    public function testMergeObjectFields(): void
    {
        $old = new Field\ObjectField('object', [
            'keep' => new Field\TextField('keep'),
            'value' => new Field\TextField('value', options: ['keep' => true]),
        ], options: ['keep' => true]);
        $new = new Field\ObjectField('object', [
            'value' => new Field\TextField('value', sortable: true, options: ['add' => true]),
            'extra' => new Field\TextField('extra'),
        ], multiple: true, options: ['add' => true]);

        $merged = (new IndexMerger())->mergeField($old, $new);

        $this->assertSame(['keep', 'value', 'extra'], \array_keys($merged->fields));
        $this->assertTrue($merged->multiple);
        $this->assertTrue($merged->fields['value']->sortable);
        $this->assertSame(['keep' => true, 'add' => true], $merged->fields['value']->options);
        $this->assertSame(['keep' => true, 'add' => true], $merged->options);
    }

    public function testMergeTypedFields(): void
    {
        $old = new Field\TypedField('blocks', 'type', [
            'text' => ['title' => new Field\TextField('title', options: ['keep' => true])],
            'keep' => ['value' => new Field\TextField('value')],
        ], options: ['keep' => true]);
        $new = new Field\TypedField('blocks', 'kind', [
            'text' => ['title' => new Field\TextField('title', facet: true)],
            'extra' => ['value' => new Field\TextField('value')],
        ], multiple: true, options: ['add' => true]);

        $merged = (new IndexMerger())->mergeField($old, $new);

        $this->assertSame(['text', 'keep', 'extra'], \array_keys($merged->types));
        $this->assertSame('kind', $merged->typeField);
        $this->assertTrue($merged->multiple);
        $this->assertTrue($merged->types['text']['title']->facet);
        $this->assertSame(['keep' => true], $merged->types['text']['title']->options);
        $this->assertSame(['keep' => true, 'add' => true], $merged->options);
    }

    public function testIdentifierOptionsAreMerged(): void
    {
        $old = new Field\IdentifierField('id', sortable: true, options: ['odm' => ['type' => 'int'], 'replace' => 'old']);
        $new = new Field\IdentifierField('id', sortable: false, options: ['replace' => 'new']);

        $merged = (new IndexMerger())->mergeFields(['id' => $old], ['id' => $new])['id'];

        $this->assertFalse($merged->sortable);
        $this->assertSame(['odm' => ['type' => 'int'], 'replace' => 'new'], $merged->options);
        $this->assertSame(['odm' => ['type' => 'int'], 'replace' => 'old'], $old->options);
    }

    public function testConflictingFieldTypes(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Field "value" must be of type "' . Field\TextField::class . '" but "' . Field\IntegerField::class . '" given.');

        (new IndexMerger())->mergeFields(['value' => new Field\TextField('value')], ['value' => new Field\IntegerField('value')]);
    }

    public function testUnsupportedFieldMerge(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Field "location" must be of type "' . Field\AbstractField::class . '"');

        (new IndexMerger())->mergeFields(['location' => new Field\GeoPointField('location')], ['location' => new Field\GeoPointField('location')]);
    }
}
