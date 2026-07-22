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

namespace CmsIg\Seal\Tests\Converter;

use CmsIg\Seal\Converter\HtmlToTextConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlToTextConverter::class)]
class HtmlToTextConverterTest extends TestCase
{
    #[DataProvider('htmlProvider')]
    public function testConvert(string $html, string $expected): void
    {
        $this->assertSame($expected, HtmlToTextConverter::convert($html));
    }

    /**
     * @return \Generator<string, array{html: string, expected: string}>
     */
    public static function htmlProvider(): \Generator
    {
        yield 'plain text' => [
            'html' => 'Plain text',
            'expected' => 'Plain text',
        ];

        yield 'empty string' => [
            'html' => '',
            'expected' => '',
        ];

        yield 'inline elements and entities' => [
            'html' => '<p>Hello <strong>world</strong> &amp; friends&nbsp;!</p>',
            'expected' => 'Hello world & friends !',
        ];

        yield 'block and break elements' => [
            'html' => '<h1>Title</h1><p>First<br>Second</p><ul><li>One</li><li>Two</li></ul>',
            'expected' => "Title\nFirst\nSecond\nOne\nTwo",
        ];

        yield 'pretty printed source' => [
            'html' => "<div>\n    Text with <em>inline</em> markup.\n</div>\n<div>Next</div>",
            'expected' => "Text with inline markup.\nNext",
        ];

        yield 'table cells' => [
            'html' => '<table><tr><th>Name</th><th>Value</th></tr><tr><td>One</td><td>Two</td></tr></table>',
            'expected' => "Name Value\nOne Two",
        ];

        yield 'media elements' => [
            'html' => 'Image<img src="image.jpg">Picture<picture></picture>Video<video>Fallback</video>Audio<audio>Fallback</audio>Canvas<canvas>Fallback</canvas>Frame<iframe>Fallback</iframe>Object<object>Fallback</object>Embed<embed>After',
            'expected' => 'Image Picture Video Fallback Audio Fallback Canvas Fallback Frame Fallback Object Fallback Embed After',
        ];

        yield 'non-visible elements' => [
            'html' => '<p>Visible</p><script>alert("hidden")</script><style>.hidden { display: none; }</style><template>Hidden</template><p>Also visible</p>',
            'expected' => "Visible\nAlso visible",
        ];

        yield 'unclosed non-visible element' => [
            'html' => '<p>Visible</p><script>alert("hidden")',
            'expected' => 'Visible',
        ];

        yield 'select options' => [
            'html' => '<select><option>One</option><option>Two</option></select>',
            'expected' => "One\nTwo",
        ];

        yield 'encoded markup remains text' => [
            'html' => '&lt;strong&gt;not markup&lt;/strong&gt;',
            'expected' => '<strong>not markup</strong>',
        ];
    }
}
