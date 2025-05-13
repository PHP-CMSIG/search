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

namespace CmsIg\Seal\Tests\Schema;

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Index::class)]
class IndexTest extends TestCase
{
    public function testIndex(): void
    {
        $index = new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
            'title_underline' => new Field\TextField('title_underline'),
            'descriptionCamelCase' => new Field\TextField('descriptionCamelCase'),
            'number01' => new Field\TextField('number01'),
            'object' => new Field\ObjectField('object', [
                'name' => new Field\TextField('name'),
            ]),
        ]);

        $this->assertSame('uuid', $index->getIdentifierField()->name);
        $this->assertSame([
            'title_underline',
            'descriptionCamelCase',
            'number01',
            'object.name',
        ], $index->searchableFields);
    }

    public function testFalseRootFieldMapping(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('A field named "title" does not match key "name" in index "test"');

        new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
            'name' => new Field\TextField('title'),
        ]);
    }

    public function testFalseIdentifiertFieldMapping(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('A field named "uuid" does not match key "id" in index "test"');

        new Index('test', [
            'id' => new Field\IdentifierField('uuid'),
        ]);
    }

    public function testFalseObjectFieldMapping(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('A field named "title" does not match key "name" in index "test"');

        new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
            'object' => new Field\ObjectField('object', [
                'name' => new Field\TextField('title'),
            ]),
        ]);
    }

    #[DataProvider('provideFalseFieldCharacter')]
    public function testFalseRootFieldCharacter(string $fieldName): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('A field named "' . $fieldName . '" in index "test" uses unsupported format');

        new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
            $fieldName => new Field\TextField($fieldName),
        ]);
    }

    #[DataProvider('provideFalseFieldCharacter')]
    public function testFalseObjectFieldCharacter(string $fieldName): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('A field named "' . $fieldName . '" in index "test" uses unsupported format');

        new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
            'object' => new Field\ObjectField('object', [
                $fieldName => new Field\TextField($fieldName),
            ]),
        ]);
    }

    public function testGetFieldByPathObject(): void
    {
        $index = new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
            'object' => new Field\ObjectField('object', [
                'name' => new Field\TextField('name'),
            ]),
        ]);

        $field = $index->getFieldByPath('object.name');
        $this->assertInstanceOf(Field\TextField::class, $field);
        $this->assertSame('name', $field->name);
    }

    public function testGetFieldByPathType(): void
    {
        $index = new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
            'blocks' => new Field\TypedField('blocks', 'type', [
                'text' => [
                    'title' => new Field\TextField('title'),
                    'description' => new Field\TextField('description'),
                    'media' => new Field\IntegerField('media', multiple: true),
                ],
                'embed' => [
                    'title' => new Field\TextField('title'),
                    'media' => new Field\TextField('media', searchable: false),
                ],
            ], multiple: true),
        ]);

        $field = $index->getFieldByPath('blocks.text.media');
        $this->assertInstanceOf(Field\IntegerField::class, $field);
        $this->assertSame('media', $field->name);
    }

    public function testGetOptions(): void
    {
        $index = new Index('test', [
            'uuid' => new Field\IdentifierField('uuid'),
        ], [
            'key' => 'value',
        ]);

        $this->assertSame([
            'key' => 'value',
        ], $index->options);
    }

    /**
     * @return \Generator<array{
     *     0: string,
     * }>
     */
    public static function provideFalseFieldCharacter(): \Generator
    {
        yield ['field.point'];
        yield ['field,comma'];
        yield ['field-minus'];
        yield ['field+plus'];
        yield ['field"quotes"'];
        yield ['field´quotes`'];
        yield ['field\'quotes\''];
        yield ['field:colon'];
        yield ['field;semicolon'];
        yield ['field<lower'];
        yield ['field>greater'];
        yield ['field^circumflex'];
        yield ['fieldümläot'];
        yield ['fieldÜmlÄÖt'];
        yield ['fieldßharp'];
        yield ['field@at'];
        yield ['field=same'];
        yield ['field(brace)'];
        yield ['field[brace]'];
        yield ['field{brace}'];
        yield ['field€uro'];
        yield ['field$ollar'];
        yield ['field#hash'];
        yield ['123'];
    }
}
