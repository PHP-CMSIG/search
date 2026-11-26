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

namespace CmsIg\Seal\Odm\Tests\Schema;

use CmsIg\Seal\Odm\Schema\Loader\AttributeLoader;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Loader\PrefixLoader;
use CmsIg\Seal\Schema\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeLoader::class)]
class AttributeLoaderTest extends TestCase
{
    public function testBasic(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/Basic',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(self::requireSchema(__DIR__ . '/Fixtures/basic.php'), $schema);
    }

    public function testPrefixLoader(): void
    {
        $attributeLoader = new AttributeLoader([__DIR__ . '/Fixtures/Basic']);
        $schema = $attributeLoader->load();
        $prefixedSchema = (new PrefixLoader($attributeLoader, 'test_'))->load();

        self::assertSame(\array_keys($schema->indexes), \array_keys($prefixedSchema->indexes));
        foreach ($schema->indexes as $name => $index) {
            $expectedIndex = self::indexToArray($index);
            $expectedIndex['name'] = 'test_' . $index->name;

            self::assertSame($expectedIndex, self::indexToArray($prefixedSchema->indexes[$name]));
        }
    }

    public function testFlags(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/Flags',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(self::requireSchema(__DIR__ . '/Fixtures/flags.php'), $schema);
    }

    public function testMultiple(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/Multiple',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(self::requireSchema(__DIR__ . '/Fixtures/multiple.php'), $schema);
    }

    public function testObject(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/Object',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(self::requireSchema(__DIR__ . '/Fixtures/object.php'), $schema);
    }

    public function testPhpDoc(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/PhpDoc',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(self::requireSchema(__DIR__ . '/Fixtures/phpdoc.php'), $schema);
    }

    public function testSearchableStringAndSpacedGeneric(): void
    {
        $schema = (new AttributeLoader([__DIR__ . '/Fixtures/Regression']))->load();
        self::assertTrue($schema->indexes['regression']->fields['title']->searchable);
        self::assertInstanceOf(Field\TextField::class, $schema->indexes['regression']->fields['tags']);
        self::assertTrue($schema->indexes['regression']->fields['tags']->multiple);
    }

    public function testMultipleIntIdentifierIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must resolve to a single string or int');
        (new AttributeLoader([__DIR__ . '/Fixtures/InvalidIntIdentifier']))->load();
    }

    public function testMultipleStringIdentifierIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must resolve to a single string or int');
        (new AttributeLoader([__DIR__ . '/Fixtures/InvalidStringIdentifier']))->load();
    }

    public function testDateTypeMetadata(): void
    {
        $schema = (new AttributeLoader([__DIR__ . '/Fixtures/Dates']))->load();
        $fields = $schema->indexes['dates']->fields;
        self::assertSame(['custom' => true, 'odm' => ['type' => \DateTime::class]], $fields['mutable']->options);
        self::assertSame(['odm' => ['type' => \DateTimeImmutable::class]], $fields['immutable']->options);
        self::assertSame(['odm' => ['type' => \DateTimeImmutable::class]], $fields['interface']->options);
        self::assertSame(['odm' => ['type' => \DateTime::class]], $fields['nullable']->options);
        self::assertSame(['odm' => ['type' => \DateTime::class]], $fields['dates']->options);
        self::assertTrue($fields['dates']->multiple);
    }

    public function testDateSubclassesAreRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DateTime subclass');
        (new AttributeLoader([__DIR__ . '/Fixtures/InvalidDateSubclass']))->load();
    }

    private static function requireSchema(string $file): Schema
    {
        /** @var Schema $schema */
        $schema = require $file;

        return $schema;
    }

    private static function assertSchema(Schema $expectedSchema, Schema $actualSchema): void
    {
        self::assertSame(
            self::schemaToArray($expectedSchema),
            self::schemaToArray($actualSchema),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function schemaToArray(Schema $schema): array
    {
        $arraySchema = [
            'indexes' => [],
        ];

        foreach ($schema->indexes as $key => $index) {
            $arraySchema['indexes'][$key] = self::indexToArray($index);
        }

        return $arraySchema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function indexToArray(Index $index): array
    {
        $arrayIndex = [
            'name' => $index->name,
            'fields' => [],
        ];

        foreach ($index->fields as $key => $field) {
            $arrayIndex['fields'][$key] = self::fieldToArray($field);
        }

        $arrayIndex['options'] = $index->options;

        return $arrayIndex;
    }

    /**
     * @return array<string, mixed>
     */
    private static function fieldToArray(Field\AbstractField $field): array
    {
        $arrayField = [
            'name' => $field->name,
            'multiple' => $field->multiple,
            'searchable' => $field->searchable,
            'filterable' => $field->filterable,
            'sortable' => $field->sortable,
            'distinct' => $field->distinct,
            'facet' => $field->facet,
        ];

        if ($field instanceof Field\ObjectField) {
            $arrayField['fields'] = [];

            foreach ($field->fields as $key => $subField) {
                $arrayField['fields'][$key] = self::fieldToArray($subField);
            }
        }

        if ($field instanceof Field\TypedField) {
            $arrayField['typeField'] = $field->typeField;
            $arrayField['types'] = [];

            foreach ($field->types as $key => $type) {
                $typedFields = [];
                foreach ($type as $typeFieldKey => $subField) {
                    $typedFields[$typeFieldKey] = self::fieldToArray($subField);
                }

                $arrayField['types'][$key] = $typedFields;
            }
        }

        $arrayField['options'] = $field->options;

        return $arrayField;
    }
}
