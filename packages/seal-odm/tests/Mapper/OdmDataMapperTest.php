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

// @php-cs-fixer-ignore php_unit_strict

namespace CmsIg\Seal\Odm\Tests\Mapper;

use CmsIg\Seal\Odm\Mapper\OdmDataMapper;
use CmsIg\Seal\Odm\Schema\Loader\AttributeLoader;
use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OdmDataMapper::class)]
class OdmDataMapperTest extends TestCase
{
    /**
     * @param array<string, mixed> $document
     */
    #[DataProvider('provideFiles')]
    public function testObjectToArray(string $index, object $object, array $document): void
    {
        $odmDataMapper = $this->createInstance(__DIR__ . '/Fixtures/Schema');

        $this->assertSame(
            $document,
            $odmDataMapper->objectToArray($index, $object),
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    #[DataProvider('provideFiles')]
    public function testArrayToObject(string $index, object $object, array $document): void
    {
        $odmDataMapper = $this->createInstance(__DIR__ . '/Fixtures/Schema');

        $this->assertEquals(
            $object,
            $odmDataMapper->arrayToObject($index, $document),
        );
    }

    /**
     * @return \Generator<string, array{
     *     index: string,
     *     object: object,
     *     document: array<string, mixed>,
     * }>
     */
    public static function provideFiles(): \Generator
    {
        yield 'basic' => require __DIR__ . '/Fixtures/basic.php'; // @phpstan-ignore-line generator.valueType
        yield 'basic_empty' => require __DIR__ . '/Fixtures/basic_empty.php'; // @phpstan-ignore-line generator.valueType
        yield 'nested' => require __DIR__ . '/Fixtures/nested.php'; // @phpstan-ignore-line generator.valueType
        yield 'nested_empty' => require __DIR__ . '/Fixtures/nested_empty.php'; // @phpstan-ignore-line generator.valueType
    }

    public function testDateTimeRoundTripPreservesInstantsAcrossTimezones(): void
    {
        $object = new class() {
            public \DateTimeImmutable $created;

            /** @var list<\DateTimeImmutable> */
            public array $dates;
        };
        $object->created = new \DateTimeImmutable('2026-07-01T12:00:00+02:00');
        $object->dates = [new \DateTimeImmutable('2026-01-01T12:00:00-05:00')];
        $mapper = new OdmDataMapper(new Schema([
            'dates' => new Index('dates', [
                'created' => new Field\DateTimeField('created'),
                'dates' => new Field\DateTimeField('dates', multiple: true),
            ], options: ['odm' => ['mappedClass' => $object::class]]),
        ]));
        $originalTimezone = \date_default_timezone_get();
        \date_default_timezone_set('UTC');

        try {
            $document = $mapper->objectToArray('dates', $object);
            self::assertSame('2026-07-01T12:00:00+02:00', $document['created']);
            self::assertSame(['2026-01-01T12:00:00-05:00'], $document['dates']);
            $hydrated = $mapper->arrayToObject('dates', $document);
            self::assertInstanceOf($object::class, $hydrated);
            self::assertSame($object->created->getTimestamp(), $hydrated->created->getTimestamp());
            self::assertSame($object->dates[0]->getTimestamp(), $hydrated->dates[0]->getTimestamp());
        } finally {
            \date_default_timezone_set($originalTimezone);
        }
    }

    private function createInstance(string $schemaDirectory): OdmDataMapper
    {
        $attributeLoader = new AttributeLoader([$schemaDirectory]);
        $schema = $attributeLoader->load();

        return new OdmDataMapper($schema);
    }
}
