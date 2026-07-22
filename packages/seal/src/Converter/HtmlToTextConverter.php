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

namespace CmsIg\Seal\Converter;

/**
 * Converts HTML into search-friendly plain text by preserving meaningful element
 * boundaries, decoding entities, and excluding non-visible content.
 *
 * @example
 *     $html = '<h1>Title</h1><p>Hello <strong>world</strong> &amp; friends.</p>';
 *     $result = HtmlToTextConverter::convert($html);
 *     // $result is "Title\nHello world & friends."
 *
 * @experimental we are searching for feedback for this class.
 */
final class HtmlToTextConverter
{
    private const NON_VISIBLE_ELEMENTS_PATTERN = '~<(script|style|template)\b[^>]*>.*?(?:</\1\s*>|\z)~is';

    private const LINE_BREAK_ELEMENTS_PATTERN = '~</?(?:address|article|aside|blockquote|body|caption|dd|details|dialog|div|dl|dt|fieldset|figcaption|figure|footer|form|h[1-6]|header|hgroup|hr|legend|li|main|menu|nav|ol|option|p|pre|search|section|summary|table|tbody|tfoot|thead|tr|ul)\b[^>]*>|<br\b[^>]*>~i';

    private const SPACE_ELEMENTS_PATTERN = '~</?(?:audio|canvas|col|colgroup|embed|iframe|img|object|picture|video)\b[^>]*>|</(?:td|th)\s*>~i';

    private function __construct()
    {
    }

    public static function convert(string $html): string
    {
        if ('' === $html) {
            return '';
        }

        // Whitespace used to format the HTML source is not meaningful. Normalize it
        // before inserting separators for elements which do affect rendered text.
        $html = (string) \preg_replace('/\s+/', ' ', $html);
        $html = (string) \preg_replace(self::NON_VISIBLE_ELEMENTS_PATTERN, '', $html);
        $html = (string) \preg_replace(self::LINE_BREAK_ELEMENTS_PATTERN, "\n", $html);
        $html = (string) \preg_replace(self::SPACE_ELEMENTS_PATTERN, ' ', $html);

        $text = \html_entity_decode(\strip_tags($html), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        // Decode entities before the final normalization so non-breaking spaces and
        // numeric whitespace entities behave like ordinary whitespace.
        $text = (string) \preg_replace('/[^\S\r\n]+/u', ' ', $text);
        $text = (string) \preg_replace('/ *\R */u', "\n", $text);
        $text = (string) \preg_replace('/\n+/', "\n", $text);

        return \trim($text);
    }
}
