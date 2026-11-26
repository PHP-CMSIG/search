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

namespace CmsIg\Seal\Adapter\Loupe\Tests;

use CmsIg\Seal\Adapter\Loupe\LoupeAdapter;
use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Testing\AbstractSearcherTestCase;
use CmsIg\Seal\Testing\TestingHelper;
use Composer\InstalledVersions;

class LoupeSearcherTest extends AbstractSearcherTestCase
{
    public static function setUpBeforeClass(): void
    {
        $helper = ClientHelper::getHelper();
        self::$adapter = new LoupeAdapter($helper);

        parent::setUpBeforeClass();
    }

    public function testZeroLimitWithFacets(): void
    {
        // Loupe below 1.1.4 returned empty facets when the limit was zero
        if (\version_compare((string) InstalledVersions::getVersion('loupe/loupe'), '1.1.4.0', '<')) {
            $this->markTestSkipped('Facets with a zero limit require loupe/loupe 1.1.4 or newer.');
        }

        parent::testZeroLimitWithFacets();
    }

    public function testSearchConditionWithHighlightOnMultipleField(): void
    {
        $documents = TestingHelper::createComplexFixtures();

        $schema = self::getSchema();

        foreach ($documents as $document) {
            self::$indexer->save($schema->indexes[TestingHelper::INDEX_COMPLEX], $document);
        }

        $search = new SearchBuilder($schema, self::$searcher);
        $search->index(TestingHelper::INDEX_COMPLEX);
        $search->addFilter(Condition::search('UX'));
        $search->highlight(['tags'], '<mark>', '</mark>');

        $loadedDocuments = [...$search->getResult()];

        $this->assertCount(2, $loadedDocuments);

        foreach ($loadedDocuments as $loadedDocument) {
            $this->assertSame(['tags' => '<mark>UX</mark>'], $loadedDocument['_formatted']);
        }

        foreach ($documents as $document) {
            self::$indexer->delete($schema->indexes[TestingHelper::INDEX_COMPLEX], $document['uuid']);
        }
    }
}
