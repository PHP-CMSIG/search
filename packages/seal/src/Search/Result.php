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

namespace CmsIg\Seal\Search;

use Symfony\Component\String\UnicodeString;

/**
 * @extends \IteratorIterator<int, array<string, mixed>, \Generator>
 */
final class Result extends \IteratorIterator
{
    /**
     * @param \Generator<int, array<string, mixed>> $documents
     */
    public function __construct(
        \Generator $documents,
        readonly private int $total,
    ) {
        parent::__construct($documents);
    }

    public function total(): int
    {
        return $this->total;
    }

    public static function createEmpty(): static
    {
        return new self((static function (): \Generator {
            yield from [];
        })(), 0);
    }

    public static function trimContext(string $context, int $numberOfContextChars, string $contextEllipsis = '[…]', string $preTag = '<mark>', string $postTag = '</mark>'): string
    {
        $context = new UnicodeString($context);
        $chunks = [];

        foreach ($context->split($preTag) as $chunk) {
            foreach ($chunk->split($postTag, 2) as $innerChunk) {
                $chunks[] = $innerChunk;
            }
        }

        if (\count($chunks) < 3 || 1 !== \count($chunks) % 2) {
            return $context->toString();
        }

        $trim = static function (UnicodeString $string, int $length, bool $fromEnd) use ($contextEllipsis): UnicodeString {
            $truncated = $fromEnd
                ? $string->reverse()->truncate($length, cut: false)->reverse()
                : $string->truncate($length, cut: false);

            if ($truncated->equalsTo($string)) {
                return $string;
            }

            return $fromEnd
                ? $truncated->prepend($contextEllipsis)
                : $truncated->append($contextEllipsis);
        };

        $result = [];

        foreach ($chunks as $i => $chunk) {
            // Even = context, Odd = highlighted key phrases
            if (0 === $i % 2) {
                // The first chunk only ever has to be prepended
                if (0 === $i) {
                    $result[] = $trim($chunk, $numberOfContextChars, true)->toString();
                    // The last chunk only ever has to be appended
                } elseif ($i === \count($chunks) - 1) {
                    $result[] = $trim($chunk, $numberOfContextChars, false)->toString();
                    // An in-between chunk has to be left untouched, if it is shorter or equal the desired context length
                } elseif ($chunk->length() <= $numberOfContextChars) {
                    $result[] = $chunk->toString();
                    // Otherwise we have to prepend and append
                } else {
                    $pre = $trim($chunk, $numberOfContextChars, true);
                    $post = $trim($chunk, $numberOfContextChars, false);

                    // If both have been shortened, we would have a double ellipsis now, so let's trim that
                    if ($post->endsWith($contextEllipsis) && $pre->startsWith($contextEllipsis)) {
                        $post = $post->trimSuffix($contextEllipsis);
                    }

                    $result[] = $post->append($pre->toString())->toString();
                }
            } else {
                // Highlighted chunk, leave that untouched with the tags
                $result[] = $chunk->prepend($preTag)->append($postTag)->toString();
            }
        }

        return implode('', $result);
    }
}
