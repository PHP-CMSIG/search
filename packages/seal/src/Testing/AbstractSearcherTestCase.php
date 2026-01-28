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

namespace CmsIg\Seal\Testing;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\Facet\Facet;
use CmsIg\Seal\Search\SearchBuilder;
use PHPUnit\Framework\TestCase;

abstract class AbstractSearcherTestCase extends TestCase
{
    protected static AdapterInterface $adapter;

    protected static SchemaManagerInterface $schemaManager;

    protected static IndexerInterface $indexer;

    protected static SearcherInterface $searcher;

    protected static Schema $schema;

    private static TaskHelper $taskHelper;

    protected function setUp(): void
    {
        self::$taskHelper = new TaskHelper();
    }

    public static function setUpBeforeClass(): void
    {
        self::$schemaManager = self::$adapter->getSchemaManager();
        self::$indexer = self::$adapter->getIndexer();
        self::$searcher = self::$adapter->getSearcher();

        self::$taskHelper = new TaskHelper();
        foreach (self::getSchema()->indexes as $index) {
            if (self::$schemaManager->existIndex($index)) {
                self::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true])->wait();
            }

            self::$taskHelper->tasks[] = self::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();
    }

    public static function tearDownAfterClass(): void
    {
        self::$taskHelper->waitForAll();

        foreach (self::getSchema()->indexes as $index) {
            self::$taskHelper->tasks[] = self::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        }

        self::$taskHelper->waitForAll();
    }

    protected function tearDown(): void
    {
        self::$taskHelper->waitForAll();
    }

    protected static function getSchema(): Schema
    {
        if (!isset(self::$schema)) {
            self::$schema = TestingHelper::createSchema();
        }

        return self::$schema;
    }

    public function testDistinctSearch(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Other'));
        $search->distinct('commentsCount');

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(1, $loadedDocuments);
        $this->assertSame(0, $loadedDocuments[0]['commentsCount']);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testCountFacet(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFacet(Facet::count(field: 'rating'));
        $search->addFacet(Facet::count(field: 'isSpecial'));

        $facets = $search->getResult()->facets();
        TestingHelper::recursiveKeySort($facets);

        $this->assertSame([
            'isSpecial' => [
                'count' => [
                    'false' => 1,
                    'true' => 1,
                ],
            ],
            'rating' => [
                'count' => [
                    '2.5' => 1,
                    '3.5' => 1,
                ],
            ],
        ], $facets);

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Other'));
        $search->addFacet(Facet::count(field: 'rating'));
        $search->addFacet(Facet::count(field: 'isSpecial'));

        $facets = $search->getResult()->facets();
        TestingHelper::recursiveKeySort($facets);

        $this->assertSame([
            'isSpecial' => [
                'count' => [
                    'false' => 1,
                ],
            ],
            'rating' => [
                'count' => [
                    '2.5' => 1,
                ],
            ],
        ], $facets);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testMinMaxFacet(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFacet(Facet::minMax(field: 'rating'));

        $facets = $search->getResult()->facets();
        TestingHelper::recursiveKeySort($facets);

        $this->assertSame([
            'rating' => [
                'max' => 3.5,
                'min' => 2.5,
            ],
        ], $facets);

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Other'));
        $search->addFacet(Facet::minMax(field: 'rating'));

        $facets = $search->getResult()->facets();
        TestingHelper::recursiveKeySort($facets);

        $this->assertSame([
            'rating' => [
                'max' => 2.5,
                'min' => 2.5,
            ],
        ], $facets);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testCountFacetOnMultiValue(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFacet(Facet::count(field: 'tags'));

        $facets = $search->getResult()->facets();
        TestingHelper::recursiveKeySort($facets);

        $this->assertSame([
            'tags' => [
                'count' => [
                    'Tech' => 2,
                    'UI' => 2,
                    'UX' => 2,
                ],
            ],
        ], $facets);

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Blog'));
        $search->addFacet(Facet::count(field: 'tags'));

        $facets = $search->getResult()->facets();
        TestingHelper::recursiveKeySort($facets);

        $this->assertSame([
            'tags' => [
                'count' => [
                    'Tech' => 1,
                    'UI' => 2,
                    'UX' => 1,
                ],
            ],
        ], $facets);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testCount(): void
    {
        $schema = self::getSchema();
        $this->assertSame(0, self::$searcher->count($schema->indexes[TestingHelper::INDEX_COMPLEX]));

        $documents = TestingHelper::createComplexFixtures();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $this->assertSame(4, self::$searcher->count($schema->indexes[TestingHelper::INDEX_COMPLEX]));

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();
    }

    public function testSearchCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Blog'));

        $expectedDocumentsVariantA = [
            $documents[0],
            $documents[1],
        ];
        $expectedDocumentsVariantB = [
            $documents[1],
            $documents[0],
        ];

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(2, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Thing'));

        $this->assertSame([$documents[2]], [...$search->getResult()]);

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('FARA25008/B'));

        $this->assertSame([$documents[0]], [...$search->getResult()]);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testSearchConditionWithHighlight(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Blog'));
        $search->highlight(['title', 'article'], '<mark>', '</mark>');

        $expectedDocumentA = $documents[0];
        $expectedDocumentA['_formatted']['title'] = \str_replace( // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
            'Blog',
            '<mark>Blog</mark>',
            $expectedDocumentA['title'] ?? '',
        );
        $expectedDocumentA['_formatted']['article'] = null; // normalize the highlight behaviour none matches returned as null for every engine
        $expectedDocumentB = $documents[1];
        $expectedDocumentB['_formatted']['title'] = \str_replace( // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
            'Blog',
            '<mark>Blog</mark>',
            $expectedDocumentB['title'] ?? '',
        );
        $expectedDocumentB['_formatted']['article'] = null; // normalize the highlight behaviour none matches returned as null for every engine

        $expectedDocumentsVariantA = [
            $expectedDocumentA,
            $expectedDocumentB,
        ];
        $expectedDocumentsVariantB = [
            $expectedDocumentB,
            $expectedDocumentA,
        ];

        $loadedDocuments = [...$search->getResult()];

        $this->assertCount(2, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('Thing'));

        $this->assertSame([$documents[2]], [...$search->getResult()]);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testNoneSearchableFields(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('admin.nonesearchablefield@localhost'));

        $this->assertCount(0, [...$search->getResult()]);
    }

    public function testLimitAndOffset(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = (new SearchBuilder($schema, self::$searcher))
            ->index(TestingHelper::INDEX_COMPLEX)
            ->addFilter(Condition::search('Blog'))
            ->limit(1);

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(1, $loadedDocuments);

        $this->assertTrue(
            [$documents[0]] === $loadedDocuments
            || [$documents[1]] === $loadedDocuments,
            'Not correct documents where found.',
        );

        $isFirstDocumentOnPage1 = [$documents[0]] === $loadedDocuments;

        $search = (new SearchBuilder($schema, self::$searcher))
            ->index(TestingHelper::INDEX_COMPLEX)
            ->addFilter(Condition::search('Blog'))
            ->offset(1)
            ->limit(1);

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(1, $loadedDocuments);
        $this->assertSame(
            $isFirstDocumentOnPage1 ? [$documents[1]] : [$documents[0]],
            $loadedDocuments,
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testEqualCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::equal('tags', 'UI'));

        $expectedDocumentsVariantA = [
            $documents[0],
            $documents[1],
        ];
        $expectedDocumentsVariantB = [
            $documents[1],
            $documents[0],
        ];

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(2, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testEqualConditionWithBoolean(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::equal('isSpecial', true));

        $expectedDocumentsVariantA = [
            $documents[0],
        ];
        $expectedDocumentsVariantB = [
            $documents[0],
        ];

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(1, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testEqualConditionSpecialString(string $specialString = "^The 17\" O'Conner && O`Series \n OR a || 1%2 1~2 1*2 \r\n book? \r \twhat \\ text: }{ )( ][ - + // \n\r ok? end$"): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $key => $document) {
            if ('79848403-c1a1-4420-bcc2-06ed537e0d4d' === $document['uuid']) {
                $document['tags'][] = $specialString;
            }

            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );

            $documents[$key] = $document;
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::equal('tags', $specialString));

        $expectedDocumentsVariantA = [
            $documents[1],
        ];
        $expectedDocumentsVariantB = [
            $documents[1],
        ];

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(1, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testMultiEqualCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::equal('tags', 'UI'));
        $search->addFilter(Condition::equal('tags', 'UX'));

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(1, $loadedDocuments);

        $this->assertSame(
            [$documents[1]],
            $loadedDocuments,
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testEqualConditionWithSearchCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::equal('tags', 'Tech'));
        $search->addFilter(Condition::search('Blog'));

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(1, $loadedDocuments);

        $this->assertSame([$documents[0]], $loadedDocuments, 'Not correct documents where found.');

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testNotEqualCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::notEqual('tags', 'UI'));

        $expectedDocumentsVariantA = [
            $documents[2],
            $documents[3],
        ];
        $expectedDocumentsVariantB = [
            $documents[3],
            $documents[2],
        ];

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(2, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testGreaterThanCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::greaterthan('rating', 2.5));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThanOrEqual(1, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $this->assertGreaterThan(2.5, $loadedDocument['rating']);
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testGreaterThanConditionWithDateField(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::greaterThan('created', '2022-12-26T12:00:00+01:00'));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThanOrEqual(1, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $created = $loadedDocument['created'] ?? '1970-01-01T00:00:00+00:00';
            $this->assertIsString($created);
            $this->assertGreaterThan(\strtotime('2022-12-26T12:00:00+01:00'), \strtotime($created));
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testGreaterThanEqualCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::greaterThanEqual('rating', 2.5));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThan(1, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $this->assertNotNull(
                $loadedDocument['rating'] ?? null,
                'Expected only documents with rating document "' . $loadedDocument['uuid'] . '" without rating returned.',  // @phpstan-ignore-line binaryOp.invalid
            );

            $this->assertGreaterThanOrEqual(2.5, $loadedDocument['rating']);
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testGreaterThanOrEqualConditionWithDateField(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::greaterThanEqual('created', '2022-12-26T12:00:00+01:00'));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThanOrEqual(2, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $created = $loadedDocument['created'] ?? '1970-01-01T00:00:00+00:00';
            $this->assertIsString($created);
            $this->assertGreaterThanOrEqual(\strtotime('2022-12-26T12:00:00+01:00'), \strtotime($created));
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testGreaterThanEqualConditionMultiValue(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::greaterthanequal('categoryIds', 3.0));

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(2, $loadedDocuments);

        foreach ($loadedDocuments as $loadedDocument) {
            /** @var int[] $categoryIds */
            $categoryIds = $loadedDocument['categoryIds'];
            $biggestCategoryId = \array_reduce($categoryIds, \max(...)); // @phpstan-ignore-line argument.type

            $this->assertNotNull($biggestCategoryId);
            $this->assertGreaterThanOrEqual(3.0, $biggestCategoryId);
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testLessThanCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::lessThan('rating', 3.5));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThanOrEqual(1, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $this->assertNotNull(
                $loadedDocument['rating'] ?? null,
                'Expected only documents with rating document "' . $loadedDocument['uuid'] . '" without rating returned.', // @phpstan-ignore-line binaryOp.invalid
            );

            $this->assertLessThan(3.5, $loadedDocument['rating']);
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testLessThanEqualCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::lessthanequal('rating', 3.5));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThan(1, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $this->assertNotNull(
                $loadedDocument['rating'] ?? null,
                'Expected only documents with rating document "' . $loadedDocument['uuid'] . '" without rating returned.', // @phpstan-ignore-line binaryOp.invalid
            );

            $this->assertLessThanOrEqual(3.5, $loadedDocument['rating']);
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testGeoDistanceCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::geoDistance(
            'location',
            // Berlin
            52.5200,
            13.4050,
            1_000_000, // 1000 km
        ));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThan(1, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $this->assertNotNull(
                $loadedDocument['location'] ?? null,
                'Expected only documents with location document "' . $loadedDocument['uuid'] . '" without location returned.', // @phpstan-ignore-line binaryOp.invalid
            );
            $this->assertIsArray($loadedDocument['location']);

            $latitude = $loadedDocument['location']['latitude'] ?? null;
            $longitude = $loadedDocument['location']['longitude'] ?? null;

            $this->assertNotNull(
                $latitude,
                'Expected only documents with location document "' . $loadedDocument['uuid'] . '" without location latitude returned.', // @phpstan-ignore-line binaryOp.invalid
            );

            $this->assertNotNull(
                $longitude,
                'Expected only documents with location document "' . $loadedDocument['uuid'] . '" without location latitude returned.', // @phpstan-ignore-line binaryOp.invalid
            );

            $distance = (int) (6_371_000 * 2 * \asin(\sqrt(
                \sin(\deg2rad($latitude - 52.5200) / 2) ** 2 +  // @phpstan-ignore-line binaryOp.invalid
                \cos(\deg2rad(52.5200)) * \cos(\deg2rad($latitude)) * \sin(\deg2rad($longitude - 13.4050) / 2) ** 2, // @phpstan-ignore-line binaryOp.invalid
            )));

            $this->assertLessThanOrEqual(6_000_000, $distance);
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testGeoBoundingBoxCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::geoBoundingBox(
            'location',
            // Dublin - Athen
            53.3498, // top
            23.7275, // right
            37.9838, // bottom
            -6.2603, // left
        ));

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThan(1, \count($loadedDocuments));

        foreach ($loadedDocuments as $loadedDocument) {
            $this->assertNotNull(
                $loadedDocument['location'] ?? null,
                'Expected only documents with location document "' . $loadedDocument['uuid'] . '" without location returned.', // @phpstan-ignore-line binaryOp.invalid
            );
            $this->assertIsArray($loadedDocument['location']);

            $latitude = $loadedDocument['location']['latitude'] ?? null;
            $longitude = $loadedDocument['location']['longitude'] ?? null;

            $this->assertNotNull(
                $latitude,
                'Expected only documents with location document "' . $loadedDocument['uuid'] . '" without location latitude returned.', // @phpstan-ignore-line binaryOp.invalid
            );

            $this->assertNotNull(
                $longitude,
                'Expected only documents with location document "' . $loadedDocument['uuid'] . '" without location latitude returned.', // @phpstan-ignore-line binaryOp.invalid
            );

            $isInBoxFunction = static function (
                float $latitude,
                float $longitude,
                float $northLatitude,
                float $eastLongitude,
                float $southLatitude,
                float $westLongitude,
            ): bool {
                // Check if the latitude is between the north and south boundaries
                $isWithinLatitude = $latitude <= $northLatitude && $latitude >= $southLatitude;

                // Check if the longitude is between the west and east boundaries
                $isWithinLongitude = $longitude >= $westLongitude && $longitude <= $eastLongitude;

                // The point is inside the bounding box if both conditions are true
                return $isWithinLatitude && $isWithinLongitude;
            };

            // TODO: Fix this test
            $isInBox = $isInBoxFunction($latitude, $longitude, 53.3498, 23.7275, 37.9838, -6.2603); // @phpstan-ignore-line binaryOp.invalid
            $this->assertTrue($isInBox, 'Document "' . $loadedDocument['uuid'] . '" is not in the box.'); // @phpstan-ignore-line binaryOp.invalid
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testLessThanEqualConditionMultiValue(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::lessThanEqual('categoryIds', 2.0));

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(2, $loadedDocuments);

        foreach ($loadedDocuments as $loadedDocument) {
            /** @var int[] $categoryIds */
            $categoryIds = $loadedDocument['categoryIds'];
            $smallestCategoryId = \array_reduce($categoryIds, static fn (int|null $categoryId, int|null $item): int|null => null !== $categoryId ? \min($categoryId, $item) : $item);

            $this->assertNotNull($smallestCategoryId);
            $this->assertLessThanOrEqual(2.0, $smallestCategoryId);
        }

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testInCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::in('tags', ['UI']));

        $expectedDocumentsVariantA = [
            $documents[0],
            $documents[1],
        ];
        $expectedDocumentsVariantB = [
            $documents[1],
            $documents[0],
        ];

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(2, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testNotInCondition(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::notIn('tags', ['UI']));

        $expectedDocumentsVariantA = [
            $documents[2],
            $documents[3],
        ];

        $expectedDocumentsVariantB = [
            $documents[3],
            $documents[2],
        ];

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(2, $loadedDocuments);

        $this->assertTrue(
            $expectedDocumentsVariantA === $loadedDocuments
            || $expectedDocumentsVariantB === $loadedDocuments,
            'Not correct documents where found.',
        );

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }

    public function testSortByAsc(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::greaterThan('rating', 0));
        $search->addSortBy('rating', 'asc');

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThan(1, \count($loadedDocuments));

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }

        $beforeRating = 0;
        foreach ($loadedDocuments as $loadedDocument) {
            $rating = $loadedDocument['rating'] ?? 0;
            $this->assertGreaterThanOrEqual($beforeRating, $rating);
            $beforeRating = $rating;
        }
    }

    public function testSortByDesc(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::greaterthan('rating', 0));
        $search->addSortBy('rating', 'desc');

        $loadedDocuments = [...$search->getResult()];
        $this->assertGreaterThan(1, \count($loadedDocuments));

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
        $beforeRating = \PHP_INT_MAX;
        foreach ($loadedDocuments as $loadedDocument) {
            $rating = $loadedDocument['rating'] ?? 0;
            $this->assertLessThanOrEqual($beforeRating, $rating);
            $beforeRating = $rating;
        }
    }

    public function testSortByTextFieldAsc(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::notequal('uuid', '97cd3e94-c17f-4c11-a22b-d9da2e5318cd'));
        $search->addSortBy('title', 'asc');

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(3, $loadedDocuments);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }

        $beforeTitle = null;
        foreach ($loadedDocuments as $loadedDocument) {
            $title = $loadedDocument['title'] ?? '';
            $this->assertSame(-1, $beforeTitle <=> $title);
            $beforeTitle = $title;
        }
    }

    public function testSortByTextFieldDesc(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );
        }
        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::notequal('uuid', '97cd3e94-c17f-4c11-a22b-d9da2e5318cd'));
        $search->addSortBy('title', 'desc');

        $loadedDocuments = [...$search->getResult()];
        $this->assertCount(3, $loadedDocuments);

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }

        $beforeTitle = null;
        foreach ($loadedDocuments as $loadedDocument) {
            $title = $loadedDocument['title'] ?? '';
            $this->assertSame(null === $beforeTitle ? -1 : 1, $beforeTitle <=> $title);
            $beforeTitle = $title;
        }
    }

    public function testSearchingWithNestedAndOrConditions(): void
    {
        $expectedDocumentIds = [];
        $documents = TestingHelper::createComplexFixtures();
        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->save(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document,
                ['return_slow_promise_result' => true],
            );

            if (!isset($document['tags'])) {
                continue;
            }

            if (\in_array('Tech', $document['tags'], true)
                && (\in_array('UX', $document['tags'], true) || (isset($document['isSpecial']) && false === $document['isSpecial']))
            ) {
                $expectedDocumentIds[] = $document['uuid'];
            }
        }
        $expectedDocumentIds = \array_unique($expectedDocumentIds);

        self::$taskHelper->waitForAll();

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);

        $condition = Condition::and(
            Condition::equal('tags', 'Tech'),
            Condition::or(
                Condition::equal('tags', 'UX'),
                Condition::equal('isSpecial', false),
            ),
        );

        $search->addFilter($condition);

        $loadedDocumentIds = \array_map(static fn (array $document) => $document['uuid'], [...$search->getResult()]);

        \sort($expectedDocumentIds);
        \sort($loadedDocumentIds);

        $this->assertSame($expectedDocumentIds, $loadedDocumentIds, 'Incorrect documents found.');

        foreach ($documents as $document) {
            self::$taskHelper->tasks[] = self::$indexer->delete(
                $schema->indexes[TestingHelper::INDEX_COMPLEX],
                $document['uuid'],
                ['return_slow_promise_result' => true],
            );
        }
    }
}
