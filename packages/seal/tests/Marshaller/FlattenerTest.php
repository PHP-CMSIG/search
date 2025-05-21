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

namespace CmsIg\Seal\Tests\Marshaller;

use CmsIg\Seal\Marshaller\Flattener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Flattener::class)]
class FlattenerTest extends TestCase
{
    /**
     * @param array<string, mixed> $unflatten
     * @param array<string, mixed> $flatten
     */
    #[DataProvider('flattenDataProvider')]
    public function testFlatten(array $unflatten, array $flatten): void
    {
        $marshaller = new Flattener();

        $this->assertSame($flatten, $marshaller->flatten($unflatten));
    }

    /**
     * @param array<string, mixed> $unflatten
     * @param array<string, mixed> $flatten
     */
    #[DataProvider('flattenDataProvider')]
    public function testUnflatten(array $unflatten, array $flatten): void
    {
        $marshaller = new Flattener();

        $this->assertSame($unflatten, $marshaller->unflatten($flatten));
    }

    /**
     * @return \Generator<array{
     *     flatten: array<string, mixed>,
     *     unflatten: array<string, mixed>,
     * }>
     */
    public static function flattenDataProvider(): \Generator
    {
        yield 'nested_one_level' => [
            'unflatten' => [
                'id' => 1,
                'title' => 'Title',
                'blocks' => [
                    [
                        'title' => 'Title 1',
                        'tags' => ['UI', 'UX'],
                    ],
                    [
                        'title' => 'Title 2',
                        'tags' => ['Tech'],
                    ],
                ],
            ],
            'flatten' => [
                'id' => 1,
                'title' => 'Title',
                'blocks.title' => ['Title 1', 'Title 2'],
                'blocks.tags' => ['UI', 'UX', 'Tech'],
                's_metadata' => \json_encode([
                    'blocks/title' => ['*/0/*', '*/1/*'],
                    'blocks/tags' => ['*/0/*/0', '*/0/*/1', '*/1/*/0'],
                ], \JSON_THROW_ON_ERROR),
            ],
        ];

        yield 'object' => [
            'unflatten' => [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'header' => [
                    'type' => 'image',
                    'media' => 1,
                ],
            ],
            'flatten' => [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'header.type' => 'image',
                'header.media' => 1,
                's_metadata' => \json_encode([
                    'header/type' => ['*/*'],
                    'header/media' => ['*/*'],
                ], \JSON_THROW_ON_ERROR),
            ],
        ];

        yield 'object_array' => [
            'unflatten' => [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'comments' => [
                    [
                        'email' => 'admin.nonesearchablefield@localhost',
                        'text' => 'Awesome blog!',
                    ],
                    [
                        'email' => 'example.nonesearchablefield@localhost',
                        'text' => 'Like this blog!',
                    ],
                ],
            ],
            'flatten' => [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'comments.email' => ['admin.nonesearchablefield@localhost', 'example.nonesearchablefield@localhost'],
                'comments.text' => ['Awesome blog!', 'Like this blog!'],
                's_metadata' => \json_encode([
                    'comments/email' => ['*/0/*', '*/1/*'],
                    'comments/text' => ['*/0/*', '*/1/*'],
                ], \JSON_THROW_ON_ERROR),
            ],
        ];

        yield 'complex_object' => [
            'unflatten' => [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'header' => [
                    'image' => [
                        'media' => 1,
                    ],
                ],
                'article' => '<article><h2>New Subtitle</h2><p>A html field with some content</p></article>',
                'blocks' => [
                    'text' => [
                        [
                            '_originalIndex' => 0,
                            'title' => 'Title',
                            'description' => '<p>Description</p>',
                            'media' => [
                                0 => 3,
                                1 => 4,
                            ],
                        ],
                        [
                            '_originalIndex' => 1,
                            'title' => 'Title 2',
                        ],
                        [
                            '_originalIndex' => 3,
                            'title' => 'Title 4',
                            'description' => '<p>Description 4</p>',
                            'media' => [
                                0 => 3,
                                1 => 4,
                            ],
                        ],
                    ],
                    'embed' => [
                        [
                            '_originalIndex' => 2,
                            'title' => 'Video',
                            'media' => 'https://www.youtube.com/watch?v=iYM2zFP3Zn0',
                        ],
                    ],
                ],
                'footer' => [
                    'title' => 'New Footer',
                ],
                'created' => 1643022000,
                'commentsCount' => 2,
                'rating' => 3.5,
                'isSpecial' => true,
                'comments' => [
                    [
                        'email' => 'admin.nonesearchablefield@localhost',
                        'text' => 'Awesome blog!',
                    ],
                    [
                        'email' => 'example.nonesearchablefield@localhost',
                        'text' => 'Like this blog!',
                    ],
                ],
                'tags' => [
                    'Tech',
                    'UI',
                ],
                'categoryIds' => [
                    1,
                    2,
                ],
                'location' => [
                    'lat' => 40.7128,
                    'lng' => -74.006,
                ],
            ],
            'flatten' => [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'header.image.media' => 1,
                'article' => '<article><h2>New Subtitle</h2><p>A html field with some content</p></article>',
                'blocks.text._originalIndex' => [0, 1, 3],
                'blocks.text.title' => [
                    'Title',
                    'Title 2',
                    'Title 4',
                ],
                'blocks.text.description' => [
                    '<p>Description</p>',
                    '<p>Description 4</p>',
                ],
                'blocks.text.media' => [3, 4, 3, 4],
                'blocks.embed._originalIndex' => [2],
                'blocks.embed.title' => ['Video'],
                'blocks.embed.media' => ['https://www.youtube.com/watch?v=iYM2zFP3Zn0'],
                'footer.title' => 'New Footer',
                'created' => 1643022000,
                'commentsCount' => 2,
                'rating' => 3.5,
                'isSpecial' => true,
                'comments.email' => ['admin.nonesearchablefield@localhost', 'example.nonesearchablefield@localhost'],
                'comments.text' => ['Awesome blog!', 'Like this blog!'],
                'tags' => [
                    'Tech',
                    'UI',
                ],
                'categoryIds' => [
                    1,
                    2,
                ],
                'location.lat' => 40.7128,
                'location.lng' => -74.006,
                's_metadata' => '{"header\/image\/media":["*\/*\/*"],"blocks\/text\/_originalIndex":["*\/*\/0\/*","*\/*\/1\/*","*\/*\/2\/*"],"blocks\/text\/title":["*\/*\/0\/*","*\/*\/1\/*","*\/*\/2\/*"],"blocks\/text\/description":["*\/*\/0\/*","*\/*\/2\/*"],"blocks\/text\/media":["*\/*\/0\/*\/0","*\/*\/0\/*\/1","*\/*\/2\/*\/0","*\/*\/2\/*\/1"],"blocks\/embed\/_originalIndex":["*\/*\/0\/*"],"blocks\/embed\/title":["*\/*\/0\/*"],"blocks\/embed\/media":["*\/*\/0\/*"],"footer\/title":["*\/*"],"comments\/email":["*\/0\/*","*\/1\/*"],"comments\/text":["*\/0\/*","*\/1\/*"],"location\/lat":["*\/*"],"location\/lng":["*\/*"]}',
            ],
        ];

        yield 'simple' => [
            'unflatten' => [
                'id' => 1,
                'title' => 'Title',
            ],
            'flatten' => [
                'id' => 1,
                'title' => 'Title',
            ],
        ];

        yield 'empty_values' => [
            'unflatten' => [
                'id' => 1,
                'title' => 'Title',
                'comments' => [],
                'rating' => null,
                'isSpecial' => null,
            ],
            'flatten' => [
                'id' => 1,
                'title' => 'Title',
                'comments' => [],
                'rating' => null,
                'isSpecial' => null,
            ],
        ];

        yield 'simple_with_multiple' => [
            'unflatten' => [
                'id' => 1,
                'title' => 'Title',
                'tags' => ['UI', 'UX'],
            ],
            'flatten' => [
                'id' => 1,
                'title' => 'Title',
                'tags' => ['UI', 'UX'],
            ],
        ];

        yield 'object_with_field_separator' => [
            'unflatten' => [
                'id' => 1,
                'header.title' => 'Title',
                'content.blocks' => [
                    [
                        'title' => 'Title 1',
                        'tags' => ['UI', 'UX'],
                    ],
                    [
                        'title' => 'Title 2',
                        'tags' => ['Tech'],
                    ],
                ],
            ],
            'flatten' => [
                'id' => 1,
                'header.title' => 'Title',
                'content.blocks.title' => ['Title 1', 'Title 2'],
                'content.blocks.tags' => ['UI', 'UX', 'Tech'],
                's_metadata' => \json_encode([
                    'content.blocks/title' => ['*/0/*', '*/1/*'],
                    'content.blocks/tags' => ['*/0/*/0', '*/0/*/1', '*/1/*/0'],
                ], \JSON_THROW_ON_ERROR),
            ],
        ];
    }
}
