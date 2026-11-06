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

namespace CmsIg\Seal\Odm\Tests;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Odm\Mapper\OdmDataMapper;
use CmsIg\Seal\Odm\OdmEngine;
use CmsIg\Seal\Odm\Schema\Loader\AttributeLoader;
use CmsIg\Seal\Odm\Tests\Reindex\Fixtures\Schema\Blog;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\SearchBuilder;
use PHPUnit\Framework\TestCase;

class OdmEngineTest extends TestCase
{
    public function testObjectWritesAndClassReads(): void
    {
        $schema = (new AttributeLoader([__DIR__ . '/Reindex/Fixtures/Schema']))->load();
        $engine = $this->createMock(EngineInterface::class);
        $odm = new OdmEngine($engine, new OdmDataMapper($schema));
        $blog = new Blog('1', 'Test');
        $engine->expects(self::once())->method('saveDocument')->with('blog', ['id' => '1', 'text' => 'Test'], [])->willReturn(null);
        $engine->expects(self::once())->method('getDocument')->with('blog', '1')->willReturn(['id' => '1', 'text' => 'Test']);
        $engine->expects(self::once())->method('deleteDocument')->with('blog', '1', [])->willReturn(null);
        $engine->expects(self::once())->method('countDocuments')->with('blog')->willReturn(3);

        $odm->saveDocument($blog);
        $loaded = $odm->getDocument(Blog::class, '1');
        self::assertSame($blog->id, $loaded->id);
        self::assertSame($blog->text, $loaded->text);
        $odm->deleteDocument(Blog::class, '1');
        self::assertSame(3, $odm->countDocuments(Blog::class));
    }

    public function testIndexOperationsResolveClass(): void
    {
        $schema = (new AttributeLoader([__DIR__ . '/Reindex/Fixtures/Schema']))->load();
        $engine = $this->createMock(EngineInterface::class);
        $odm = new OdmEngine($engine, new OdmDataMapper($schema));
        $options = ['return_slow_promise_result' => true];
        foreach (['createIndex', 'updateIndex', 'dropIndex'] as $method) {
            $engine->expects(self::once())->method($method)->with('blog', $options)->willReturn(null);
        }
        $engine->expects(self::once())->method('existIndex')->with('blog')->willReturn(true);

        $odm->createIndex(Blog::class, $options);
        $odm->updateIndex(Blog::class, $options);
        self::assertTrue($odm->existIndex(Blog::class));
        $odm->dropIndex(Blog::class, $options);
    }

    public function testDeletionOnlyBulkResolvesClass(): void
    {
        $schema = (new AttributeLoader([__DIR__ . '/Reindex/Fixtures/Schema']))->load();
        $engine = $this->createMock(EngineInterface::class);
        $odm = new OdmEngine($engine, new OdmDataMapper($schema));
        $engine->expects(self::once())->method('bulk')->willReturnCallback(static function (string $index, iterable $saves, iterable $deletes, int $size, array $options) {
            self::assertSame('blog', $index);
            self::assertSame([], [...$saves]);
            self::assertSame(['1'], [...$deletes]);
            self::assertSame(25, $size);
            self::assertSame([], $options);

            return null;
        });

        $odm->bulk(Blog::class, [], ['1'], 25);
    }

    public function testBulkRejectsWrongDocumentClass(): void
    {
        $schema = (new AttributeLoader([__DIR__ . '/Reindex/Fixtures/Schema']))->load();
        $engine = $this->createMock(EngineInterface::class);
        $engine->method('bulk')->willReturnCallback(static function (string $index, iterable $saves) {
            foreach ($saves as $document) {
                self::fail('Incompatible objects must be rejected before yielding documents.');
            }

            return null;
        });
        $odm = new OdmEngine($engine, new OdmDataMapper($schema));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must be an instance');
        // Deliberately pass an incompatible object to check runtime validation.
        $objects = [new \stdClass()];
        $odm->bulk(Blog::class, $objects, []);
    }

    public function testSearchHydratesRequestedClass(): void
    {
        $schema = (new AttributeLoader([__DIR__ . '/Reindex/Fixtures/Schema']))->load();
        $engine = $this->createMock(EngineInterface::class);
        $searcher = $this->createMock(SearcherInterface::class);
        $searcher->method('search')->willReturn(new Result((static function (): \Generator {
            yield ['id' => '1', 'text' => 'Test'];
        })(), 1, ['tags' => []]));
        $engine->expects(self::once())->method('createSearchBuilder')->with('blog')->willReturn((new SearchBuilder($schema, $searcher))->index('blog'));
        $odm = new OdmEngine($engine, new OdmDataMapper($schema));

        $builder = $odm->createSearchBuilder(Blog::class);
        self::assertNotSame($builder, $builder->index(Blog::class));
        $result = $builder->limit(10)->getResult();
        $objects = [...$result];
        self::assertCount(1, $objects);
        self::assertSame('1', $objects[0]->id);
        self::assertSame('Test', $objects[0]->text);
        self::assertSame(1, $result->total());
        self::assertSame(['tags' => []], $result->facets());
    }

    public function testMappingUsesLoadedSchema(): void
    {
        $mapper = new OdmDataMapper(new Schema([
            'custom' => new Index('custom', [], options: ['odm' => ['mappedClass' => Blog::class]]),
        ]));

        self::assertSame('custom', $mapper->getIndexName(Blog::class));
    }

    public function testUnmappedClassIsRejected(): void
    {
        $mapper = new OdmDataMapper(new Schema([]));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not mapped to an index');
        $mapper->getIndexName(Blog::class);
    }

    public function testAmbiguousClassIsRejected(): void
    {
        $mapper = new OdmDataMapper(new Schema([
            'one' => new Index('one', [], options: ['odm' => ['mappedClass' => Blog::class]]),
            'two' => new Index('two', [], options: ['odm' => ['mappedClass' => Blog::class]]),
        ]));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is mapped to multiple indexes');
        $mapper->getIndexName(Blog::class);
    }
}
