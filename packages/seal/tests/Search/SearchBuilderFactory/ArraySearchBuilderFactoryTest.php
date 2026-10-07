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

namespace CmsIg\Seal\Tests\Search\SearchBuilderFactory;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\Facet\Facet;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use CmsIg\Seal\Search\SearchBuilder;
use CmsIg\Seal\Search\SearchBuilderFactory\ArraySearchBuilderFactory;
use CmsIg\Seal\Search\SearchBuilderFactory\SearchBuilderFactoryConfig;
use CmsIg\Seal\Testing\TestingHelper;
use PHPUnit\Framework\TestCase;

class ArraySearchBuilderFactoryTest extends TestCase
{
    public function testSerializeAndDeserializeSearchBuilder(): void
    {
        $factory = $this->createArraySearchBuilderFactory();

        $builder = $this->createSearchBuilder()
            ->addFilter(Condition::search('cms'))
            ->addFilter(Condition::or(
                Condition::equal('tags', 'php'),
                Condition::greaterThanEqual('rating', 4.5),
            ))
            ->addFilter(Condition::geoDistance('location', 47.3769, 8.5417, 5000))
            ->addSortBy('rating', 'desc')
            ->limit(20)
            ->offset(40)
            ->highlight(['title', 'article'])
            ->distinct('commentsCount')
            ->addFacet(Facet::count('tags', ['limit' => 10]))
            ->addFacet(Facet::minMax('rating'));

        $serialized = $factory->toArray($builder);
        $deserialized = $factory->buildFromJson(
            TestingHelper::INDEX_COMPLEX,
            new SearchBuilderFactoryConfig(
                filterFields: ['tags', 'rating', 'location'],
                sortFields: ['rating'],
                facetFields: ['tags', 'rating'],
                distinctFields: ['commentsCount'],
                highlightFields: ['title', 'article'],
                allowSearch: true,
                maxLimit: 20,
            ),
            $factory->toJson($builder),
        );

        $this->assertSame($serialized, $factory->toArray($deserialized));
        $this->assertArrayNotHasKey('index', $serialized);
    }

    public function testDeserializeSearchBuilderWithValidationAllowList(): void
    {
        $builder = $this->createArraySearchBuilderFactory()->build(TestingHelper::INDEX_COMPLEX, new SearchBuilderFactoryConfig(
            filterFields: ['tags'],
            sortFields: ['rating'],
            facetFields: ['tags'],
            maxLimit: 20,
        ), [
            'filters' => [
                [
                    'type' => 'equal',
                    'field' => 'tags',
                    'value' => 'php',
                ],
            ],
            'sortBys' => [
                'rating' => 'desc',
            ],
            'limit' => 10,
            'facets' => [
                [
                    'type' => 'count',
                    'field' => 'tags',
                ],
            ],
        ]);

        $this->assertSame('tags', $builder->getSearch()->facets[0]->field);
    }

    public function testDeserializeSearchBuilderRejectsDefaultConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search conditions are not allowed by this search builder factory config.');

        $this->createArraySearchBuilderFactory()->build(TestingHelper::INDEX_COMPLEX, new SearchBuilderFactoryConfig(), [
            'filters' => [
                [
                    'type' => 'search',
                    'query' => 'cms',
                ],
            ],
        ]);
    }

    public function testDeserializeSearchBuilderRejectsFieldsOutsideValidationAllowList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search builder filter "rating" is not allowed by this config.');

        $this->createArraySearchBuilderFactory()->build(TestingHelper::INDEX_COMPLEX, new SearchBuilderFactoryConfig(filterFields: ['tags']), [
            'filters' => [
                [
                    'type' => 'equal',
                    'field' => 'rating',
                    'value' => 5,
                ],
            ],
        ]);
    }

    public function testDeserializeSearchBuilderRejectsFieldsUnsupportedBySchema(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search builder filterable field "title" is not configured as filterable');

        $this->createArraySearchBuilderFactory()->build(TestingHelper::INDEX_COMPLEX, new SearchBuilderFactoryConfig(
            filterFields: ['title'],
        ), [
            'filters' => [
                [
                    'type' => 'equal',
                    'field' => 'title',
                    'value' => 'cms',
                ],
            ],
        ]);
    }

    public function testDeserializeSearchBuilderRejectsLimitAboveValidationMaximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search builder limit "11" exceeds the allowed maximum of "10".');

        $this->createArraySearchBuilderFactory()->build(TestingHelper::INDEX_COMPLEX, new SearchBuilderFactoryConfig(maxLimit: 10), [
            'limit' => 11,
        ]);
    }

    private function createSearchBuilder(): SearchBuilder
    {
        return (new SearchBuilder(TestingHelper::createSchema(), $this->createSearcher()))
            ->index(TestingHelper::INDEX_COMPLEX);
    }

    private function createArraySearchBuilderFactory(): ArraySearchBuilderFactory
    {
        $engine = $this->createMock(EngineInterface::class);
        $engine->method('createSearchBuilder')
            ->willReturnCallback(
                fn (string $index): SearchBuilder => (new SearchBuilder(TestingHelper::createSchema(), $this->createSearcher()))
                    ->index($index),
            );

        return new ArraySearchBuilderFactory($engine);
    }

    private function createSearcher(): SearcherInterface
    {
        return new class() implements SearcherInterface {
            public function search(Search $search): Result
            {
                return Result::createEmpty();
            }

            public function count(Index $index): int
            {
                return 0;
            }
        };
    }
}
