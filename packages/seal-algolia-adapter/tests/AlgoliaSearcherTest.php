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

namespace CmsIg\Seal\Adapter\Algolia\Tests;

use CmsIg\Seal\Adapter\Algolia\AlgoliaAdapter;
use CmsIg\Seal\Adapter\Algolia\AlgoliaSearcher;
use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Testing\AbstractSearcherTestCase;
use CmsIg\Seal\Testing\TestingHelper;
use PHPUnit\Framework\Attributes\DataProvider;

class AlgoliaSearcherTest extends AbstractSearcherTestCase
{
    public static function setUpBeforeClass(): void
    {
        $client = ClientHelper::getClient();
        self::$adapter = new AlgoliaAdapter($client);

        parent::setUpBeforeClass();
    }

    /**
     * @param object[] $conditions
     */
    #[DataProvider('provideFilterConditions')]
    public function testRecursiveResolveFilterConditions(array $conditions, string $expectedFilters): void
    {
        $searcher = (new \ReflectionClass(AlgoliaSearcher::class))->newInstanceWithoutConstructor();
        $index = self::getSchema()->indexes[TestingHelper::INDEX_COMPLEX];

        $query = '';
        $geoFilters = [];
        $filters = (fn (): string => $this->recursiveResolveFilterConditions($index, $conditions, true, $query, $geoFilters))->call($searcher);

        $this->assertSame($expectedFilters, $filters);
    }

    /**
     * @return iterable<string, array{0: object[], 1: string}>
     */
    public static function provideFilterConditions(): iterable
    {
        yield 'no conditions' => [[], ''];

        yield 'search only' => [[Condition::search('Blog')], ''];

        yield 'equal' => [
            [Condition::equal('locale', 'en_GB')],
            'locale:"en_GB"',
        ];

        yield 'equal with brackets in value' => [
            [Condition::equal('locale', 'en)(GB')],
            'locale:"en)(GB"',
        ];

        yield 'equal and boolean equal' => [
            [Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true)],
            'locale:"en_GB" AND isSpecial:true',
        ];

        yield 'not equal' => [
            [Condition::notEqual('locale', 'en_GB')],
            'NOT locale:"en_GB"',
        ];

        yield 'greater than' => [
            [Condition::greaterThan('rating', 3.5)],
            'rating > 3.5',
        ];

        yield 'in with one value' => [
            [Condition::in('tags', ['UI'])],
            'tags:"UI"',
        ];

        yield 'in with multiple values' => [
            [Condition::in('tags', ['UI', 'UX'])],
            'tags:"UI" OR tags:"UX"',
        ];

        yield 'equal and in with one value' => [
            [Condition::equal('locale', 'en_GB'), Condition::in('tags', ['UI'])],
            'locale:"en_GB" AND tags:"UI"',
        ];

        yield 'in with one value and equal' => [
            [Condition::in('tags', ['UI']), Condition::equal('locale', 'en_GB')],
            'tags:"UI" AND locale:"en_GB"',
        ];

        yield 'equal and in with multiple values' => [
            [Condition::equal('locale', 'en_GB'), Condition::in('tags', ['UI', 'UX'])],
            'locale:"en_GB" AND (tags:"UI" OR tags:"UX")',
        ];

        yield 'in with multiple values and equal' => [
            [Condition::in('tags', ['UI', 'UX']), Condition::equal('locale', 'en_GB')],
            '(tags:"UI" OR tags:"UX") AND locale:"en_GB"',
        ];

        yield 'not in with one value' => [
            [Condition::notIn('tags', ['UI'])],
            'NOT tags:"UI"',
        ];

        yield 'not in with multiple values' => [
            [Condition::notIn('tags', ['UI', 'UX'])],
            'NOT tags:"UI" AND NOT tags:"UX"',
        ];

        yield 'equal and not in with multiple values' => [
            [Condition::equal('locale', 'en_GB'), Condition::notIn('tags', ['UI', 'UX'])],
            'locale:"en_GB" AND NOT tags:"UI" AND NOT tags:"UX"',
        ];

        yield 'and' => [
            [Condition::and(Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true))],
            'locale:"en_GB" AND isSpecial:true',
        ];

        yield 'nested and' => [
            [Condition::and(
                Condition::and(Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true)),
                Condition::equal('tags', 'UI'),
            )],
            'locale:"en_GB" AND isSpecial:true AND tags:"UI"',
        ];

        yield 'and with in with multiple values' => [
            [Condition::and(Condition::in('tags', ['UI', 'UX']), Condition::equal('locale', 'en_GB'))],
            '(tags:"UI" OR tags:"UX") AND locale:"en_GB"',
        ];

        yield 'or' => [
            [Condition::or(Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true))],
            'locale:"en_GB" OR isSpecial:true',
        ];

        yield 'nested or' => [
            [Condition::or(
                Condition::or(Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true)),
                Condition::equal('tags', 'UI'),
            )],
            'locale:"en_GB" OR isSpecial:true OR tags:"UI"',
        ];

        yield 'or with in with multiple values' => [
            [Condition::or(Condition::in('tags', ['UI', 'UX']), Condition::equal('locale', 'en_GB'))],
            'tags:"UI" OR tags:"UX" OR locale:"en_GB"',
        ];

        yield 'and in or' => [
            [Condition::or(
                Condition::and(Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true)),
                Condition::equal('tags', 'UI'),
            )],
            '(locale:"en_GB" AND isSpecial:true) OR tags:"UI"',
        ];

        yield 'or in and' => [
            [Condition::and(
                Condition::equal('tags', 'Tech'),
                Condition::or(Condition::equal('tags', 'UX'), Condition::equal('isSpecial', false)),
            )],
            'tags:"Tech" AND (tags:"UX" OR isSpecial:false)',
        ];

        yield 'two ors' => [
            [
                Condition::or(Condition::equal('locale', 'en_GB'), Condition::equal('locale', 'de_AT')),
                Condition::or(Condition::equal('tags', 'UI'), Condition::equal('tags', 'UX')),
            ],
            '(locale:"en_GB" OR locale:"de_AT") AND (tags:"UI" OR tags:"UX")',
        ];

        yield 'not in with multiple values in or' => [
            [Condition::or(Condition::equal('locale', 'en_GB'), Condition::notIn('tags', ['UI', 'UX']))],
            'locale:"en_GB" OR (NOT tags:"UI" AND NOT tags:"UX")',
        ];

        yield 'group with one condition' => [
            [Condition::and(Condition::or(Condition::equal('locale', 'en_GB')))],
            'locale:"en_GB"',
        ];

        yield 'group with one condition next to other condition' => [
            [Condition::equal('tags', 'UI'), Condition::or(Condition::equal('locale', 'en_GB'))],
            'tags:"UI" AND locale:"en_GB"',
        ];

        yield 'deeply nested and and or' => [
            [Condition::and(
                Condition::or(
                    Condition::equal('locale', 'en_GB'),
                    Condition::and(
                        Condition::equal('locale', 'de_AT'),
                        Condition::or(Condition::equal('tags', 'UI'), Condition::equal('tags', 'UX')),
                    ),
                ),
                Condition::equal('tags', 'Tech'),
            )],
            '(locale:"en_GB" OR (locale:"de_AT" AND (tags:"UI" OR tags:"UX"))) AND tags:"Tech"',
        ];

        yield 'nested groups with one condition' => [
            [Condition::and(Condition::and(Condition::and(Condition::equal('locale', 'en_GB'))))],
            'locale:"en_GB"',
        ];

        yield 'or with one and group' => [
            [Condition::or(Condition::and(Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true)))],
            'locale:"en_GB" AND isSpecial:true',
        ];

        yield 'two in with multiple values' => [
            [Condition::in('tags', ['UI', 'UX']), Condition::in('locale', ['en_GB', 'de_AT'])],
            '(tags:"UI" OR tags:"UX") AND (locale:"en_GB" OR locale:"de_AT")',
        ];

        yield 'and with not in and in with multiple values' => [
            [Condition::and(Condition::notIn('tags', ['UI', 'UX']), Condition::in('locale', ['en_GB', 'de_AT']))],
            'NOT tags:"UI" AND NOT tags:"UX" AND (locale:"en_GB" OR locale:"de_AT")',
        ];

        yield 'or with not equal and in with multiple values' => [
            [Condition::or(Condition::notEqual('locale', 'en_GB'), Condition::in('tags', ['UI', 'UX']))],
            'NOT locale:"en_GB" OR tags:"UI" OR tags:"UX"',
        ];

        yield 'and with one in condition with one value next to other condition' => [
            [Condition::equal('locale', 'en_GB'), Condition::and(Condition::in('tags', ['UI']))],
            'locale:"en_GB" AND tags:"UI"',
        ];

        yield 'and with one or group next to other condition' => [
            [Condition::equal('locale', 'en_GB'), Condition::and(Condition::or(Condition::equal('tags', 'UI')))],
            'locale:"en_GB" AND tags:"UI"',
        ];

        yield 'or with one and group with one or group next to other condition' => [
            [
                Condition::equal('locale', 'en_GB'),
                Condition::or(Condition::and(Condition::or(Condition::equal('tags', 'UI'), Condition::equal('tags', 'UX')))),
            ],
            'locale:"en_GB" AND (tags:"UI" OR tags:"UX")',
        ];

        yield 'or with and with in with multiple values' => [
            [Condition::or(
                Condition::and(Condition::equal('locale', 'en_GB'), Condition::in('tags', ['UI', 'UX'])),
                Condition::equal('isSpecial', true),
            )],
            '(locale:"en_GB" AND (tags:"UI" OR tags:"UX")) OR isSpecial:true',
        ];

        yield 'and with or with not in with multiple values' => [
            [Condition::and(
                Condition::or(Condition::equal('locale', 'en_GB'), Condition::notIn('tags', ['UI', 'UX'])),
                Condition::equal('isSpecial', true),
            )],
            '(locale:"en_GB" OR (NOT tags:"UI" AND NOT tags:"UX")) AND isSpecial:true',
        ];

        yield 'two not in with multiple values' => [
            [Condition::notIn('tags', ['UI', 'UX']), Condition::notIn('locale', ['en_GB', 'de_AT'])],
            'NOT tags:"UI" AND NOT tags:"UX" AND NOT locale:"en_GB" AND NOT locale:"de_AT"',
        ];

        yield 'or with two not in with multiple values' => [
            [Condition::or(
                Condition::notIn('tags', ['UI', 'UX']),
                Condition::notIn('locale', ['en_GB', 'de_AT']),
            )],
            '(NOT tags:"UI" AND NOT tags:"UX") OR (NOT locale:"en_GB" AND NOT locale:"de_AT")',
        ];

        yield 'or with two in with multiple values' => [
            [Condition::or(
                Condition::in('tags', ['UI', 'UX']),
                Condition::in('locale', ['en_GB', 'de_AT']),
            )],
            'tags:"UI" OR tags:"UX" OR locale:"en_GB" OR locale:"de_AT"',
        ];

        yield 'nested and with in and not in with multiple values' => [
            [Condition::and(
                Condition::and(Condition::in('tags', ['UI', 'UX']), Condition::equal('isSpecial', true)),
                Condition::notIn('locale', ['en_GB', 'de_AT']),
            )],
            '(tags:"UI" OR tags:"UX") AND isSpecial:true AND NOT locale:"en_GB" AND NOT locale:"de_AT"',
        ];

        yield 'or with and with or' => [
            [Condition::or(
                Condition::and(
                    Condition::or(Condition::equal('locale', 'en_GB'), Condition::equal('locale', 'de_AT')),
                    Condition::equal('tags', 'UI'),
                ),
                Condition::equal('isSpecial', true),
            )],
            '((locale:"en_GB" OR locale:"de_AT") AND tags:"UI") OR isSpecial:true',
        ];

        yield 'or with two and groups' => [
            [Condition::or(
                Condition::and(Condition::equal('locale', 'en_GB'), Condition::equal('isSpecial', true)),
                Condition::and(Condition::equal('tags', 'UI'), Condition::equal('tags', 'UX')),
            )],
            '(locale:"en_GB" AND isSpecial:true) OR (tags:"UI" AND tags:"UX")',
        ];

        yield 'or with in with one value' => [
            [Condition::or(Condition::in('tags', ['UI']), Condition::equal('locale', 'en_GB'))],
            'tags:"UI" OR locale:"en_GB"',
        ];

        yield 'or with not in with one value' => [
            [Condition::or(Condition::notIn('tags', ['UI']), Condition::equal('locale', 'en_GB'))],
            'NOT tags:"UI" OR locale:"en_GB"',
        ];

        yield 'and with or with in and or with not in' => [
            [Condition::and(
                Condition::or(Condition::in('tags', ['UI', 'UX']), Condition::equal('locale', 'en_GB')),
                Condition::or(Condition::notIn('tags', ['Tech', 'News']), Condition::equal('isSpecial', true)),
            )],
            '(tags:"UI" OR tags:"UX" OR locale:"en_GB") AND ((NOT tags:"Tech" AND NOT tags:"News") OR isSpecial:true)',
        ];

        yield 'and with one not in with multiple values' => [
            [Condition::and(Condition::notIn('tags', ['UI', 'UX']))],
            'NOT tags:"UI" AND NOT tags:"UX"',
        ];

        yield 'nested and with in with one value and not in with one value' => [
            [Condition::and(
                Condition::and(Condition::in('tags', ['UI'])),
                Condition::notIn('locale', ['en_GB']),
            )],
            'tags:"UI" AND NOT locale:"en_GB"',
        ];
    }
}
