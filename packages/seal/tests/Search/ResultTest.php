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

namespace CmsIg\Seal\Tests\Search;

use CmsIg\Seal\Search\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testIteratingResult(): void
    {
        $result = new Result((static function (): \Generator {
            yield ['id' => 42];
        })(), 1);

        $this->assertSame(1, $result->total());
        $this->assertSame([['id' => 42]], \iterator_to_array($result));
    }

    public function testCreateEmptyResult(): void
    {
        $result = Result::createEmpty();
        $this->assertSame(0, $result->total());
        $this->assertSame([], \iterator_to_array($result));
    }

    #[DataProvider('trimContextProvider')]
    public function testTrimContext(string $context, int $numberOfContextWords, string $expectedContext, string $preTag = '<em>', string $postTag = '</em>', string $contextEllipsis = '[…]'): void
    {
        $this->assertSame($expectedContext, Result::trimContext($context, $numberOfContextWords, $contextEllipsis, $preTag, $postTag));
    }

    public static function trimContextProvider(): \Generator
    {
        yield 'Basic example' => [
            'Lorem ipsum dolor sit amet, <em>consectetur</em> adipiscing elit. Etiam eleifend, augue in dictum lacinia, nisi lacus mollis <em>massa</em>, a pulvinar felis dui nec nisl. Pellentesque justo erat, sollicitudin ac dolor finibus, dapibus lacinia diam.',
            20,
            '[…]ipsum dolor sit amet, <em>consectetur</em> adipiscing elit. Etiam[…]lacinia, nisi lacus mollis <em>massa</em>, a pulvinar felis dui[…]',
        ];

        yield 'Match at the beginning' => [
            '<em>Quisque</em> maximus nec odio sed gravida. Donec ut risus ut urna auctor feugiat.',
            30,
            '<em>Quisque</em> maximus nec odio sed gravida.[…]',
        ];

        yield 'Context overlapping the end of a sentence' => [
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
            30,
            '[…]brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
        ];

        yield 'Context overlapping the start of a sentence' => [
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile and thus this sentence went on forever.',
            40,
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile and thus this sentence went[…]',
        ];

        yield 'Test with different tags' => [
            'The quick brown fox jumps over the lazy <strong>dog</strong>. The <strong>fox</strong> was very agile.',
            30,
            '[…]brown fox jumps over the lazy <strong>dog</strong>. The <strong>fox</strong> was very agile.',
            '<strong>',
            '</strong>',
        ];

        yield 'Test with multiple word matches tags' => [
            'The quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and thus this sentence went on forever.',
            30,
            'The quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and thus this[…]',
        ];

        yield 'Test with non-matching highlight tags just leaves the content untouched' => [
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
            30,
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
            '<strong>',
            '</strong>',
        ];

        yield 'Test with different ellipsis' => [
            'The quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and thus this sentence went on forever.',
            20,
            '~~~quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and~~~',
            '<em>',
            '</em>',
            '~~~',
        ];
    }
}
