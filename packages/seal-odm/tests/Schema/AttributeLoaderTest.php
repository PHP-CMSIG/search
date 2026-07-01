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

        self::assertSchema(require_once(__DIR__ . '/Fixtures/basic.php'), $schema);
    }

    public function testFlags(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/Flags',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(require_once(__DIR__ . '/Fixtures/flags.php'), $schema);
    }

    public function testMultiple(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/Multiple',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(require_once(__DIR__ . '/Fixtures/multiple.php'), $schema);
    }

    public function testObject(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/Object',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(require_once(__DIR__ . '/Fixtures/object.php'), $schema);
    }

    public function testPhpDoc(): void
    {
        $attributeLoader = new AttributeLoader([
            __DIR__ . '/Fixtures/PhpDoc',
        ]);

        $schema = $attributeLoader->load();

        self::assertSchema(require_once(__DIR__ . '/Fixtures/phpdoc.php'), $schema);
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
