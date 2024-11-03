<?php

declare(strict_types=1);

/*
 * This file is part of the Schranz Search package.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Schranz\Search\SEAL\Tests\Marshaller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Schranz\Search\SEAL\Marshaller\FlattenMarshaller;
use Schranz\Search\SEAL\Schema\Field;

#[CoversClass(FlattenMarshaller::class)]
class FlattenMarshallerTest extends TestCase
{
    /**
     * @param array<string, mixed> $document
     * @param array<string, mixed> $flattenDocument
     * @param Field\AbstractField[] $fields
     */
    #[DataProvider('provideData')]
    public function testMarshall(array $document, array $flattenDocument, array $fields): void
    {
        $marshaller = new FlattenMarshaller(addRawFilterTextField: true);

        $marshalledDocument = $marshaller->marshall($fields, $document);

        $this->assertSame([...$flattenDocument, '_source' => \json_encode($document, \JSON_THROW_ON_ERROR)], $marshalledDocument);
    }

    /**
     * @param array<string, mixed> $document
     * @param array<string, mixed> $flattenDocument
     * @param Field\AbstractField[] $fields
     */
    #[DataProvider('provideData')]
    public function testUnmarshall(array $document, array $flattenDocument, array $fields): void
    {
        $marshaller = new FlattenMarshaller(addRawFilterTextField: true);

        $flattenDocument['_source'] = \json_encode($document, \JSON_THROW_ON_ERROR);
        $unmarshalledDocument = $marshaller->unmarshall($fields, $flattenDocument);

        $this->assertSame($document, $unmarshalledDocument);
    }

    /**
     * @return \Generator<string, array{
     *     0: array<string, mixed>,
     *     1: array<string, mixed>,
     *     2: Field\AbstractField[],
     * }>
     */
    public static function provideData(): \Generator
    {
        yield 'complex_object' => [
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'header' => [
                    'type' => 'image',
                    'media' => 1,
                ],
                'article' => '<article><h2>New Subtitle</h2><p>A html field with some content</p></article>',
                'blocks' => [
                    [
                        'type' => 'text',
                        'title' => 'Titel',
                        'description' => '<p>Description</p>',
                        'media' => [3, 4],
                    ],
                    [
                        'type' => 'text',
                        'title' => 'Titel 2',
                        'description' => null,
                    ],
                    [
                        'type' => 'embed',
                        'title' => 'Video',
                        'media' => 'https://www.youtube.com/watch?v=iYM2zFP3Zn0',
                    ],
                    [
                        'type' => 'text',
                        'title' => 'Titel 4',
                        'description' => '<p>Description 4</p>',
                        'media' => [3, 4],
                    ],
                ],
                'footer' => [
                    'title' => 'New Footer',
                ],
                'created' => '2022-01-24T12:00:00+01:00',
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
                'tags' => ['Tech', 'UI'],
                'categoryIds' => [1, 2],
                'location' => [
                    'latitude' => 40.7128,
                    'longitude' => -74.006,
                ],
            ],
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'title' => 'New Blog',
                'header.image.media' => 1,
                'article' => '<article><h2>New Subtitle</h2><p>A html field with some content</p></article>',
                'blocks.text.title' => ['Titel', 'Titel 2', 'Titel 4'],
                'blocks.text.description' => ['<p>Description</p>', null, '<p>Description 4</p>'],
                'blocks.text.media' => [3, 4, 3, 4],
                'blocks.embed.title' => ['Video'],
                'blocks.embed.media' => ['https://www.youtube.com/watch?v=iYM2zFP3Zn0'],
                'footer.title' => 'New Footer',
                'created' => '2022-01-24T12:00:00+01:00',
                'commentsCount' => 2,
                'rating' => 3.5,
                'isSpecial' => true,
                'comments.email' => ['admin.nonesearchablefield@localhost', 'example.nonesearchablefield@localhost'],
                'comments.text' => ['Awesome blog!', 'Like this blog!'],
                'tags' => ['Tech', 'UI'],
                'categoryIds' => [1, 2],
                'location' => [
                    'latitude' => 40.7128,
                    'longitude' => -74.006,
                ],
                'tags.raw' => ['Tech', 'UI'],
            ],
            [
                'uuid' => new Field\IdentifierField('uuid'),
                'title' => new Field\TextField('title'),
                'header' => new Field\TypedField('header', 'type', [
                    'image' => [
                        'media' => new Field\IntegerField('media', searchable: false),
                    ],
                    'video' => [
                        'media' => new Field\TextField('media', searchable: false),
                    ],
                ]),
                'article' => new Field\TextField('article'),
                'blocks' => new Field\TypedField('blocks', 'type', [
                    'text' => [
                        'title' => new Field\TextField('title'),
                        'description' => new Field\TextField('description'),
                        'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                    ],
                    'embed' => [
                        'title' => new Field\TextField('title'),
                        'media' => new Field\TextField('media', searchable: false),
                    ],
                ], multiple: true),
                'footer' => new Field\ObjectField('footer', [
                    'title' => new Field\TextField('title'),
                ]),
                'created' => new Field\DateTimeField('created', filterable: true, sortable: true),
                'commentsCount' => new Field\IntegerField('commentsCount', searchable: false, filterable: true, sortable: true),
                'rating' => new Field\FloatField('rating', searchable: false, filterable: true, sortable: true),
                'isSpecial' => new Field\BooleanField('rating', searchable: false, filterable: true),
                'comments' => new Field\ObjectField('comments', [
                    'email' => new Field\TextField('email', searchable: false),
                    'text' => new Field\TextField('text'),
                ], multiple: true),
                'tags' => new Field\TextField('tags', multiple: true, filterable: true),
                'categoryIds' => new Field\IntegerField('categoryIds', multiple: true, searchable: false, filterable: true),
                'location' => new Field\GeoPointField('location', filterable: true, sortable: true),
            ],
        ];

        yield 'nested_object' => [
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'object' => [
                    'title' => 'Title',
                    'secondaryObject' => [
                        'title' => 'Secondary Title',
                        'tertiaryObject' => [
                            'title' => 'Tertiary Title',
                            'media' => [1, 2],
                            'quaternaryObject' => [
                                'title' => 'Quaternary Title',
                                'media' => [3, 4],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'object.title' => 'Title',
                'object.secondaryObject.title' => 'Secondary Title',
                'object.secondaryObject.tertiaryObject.title' => 'Tertiary Title',
                'object.secondaryObject.tertiaryObject.media' => [1, 2],
                'object.secondaryObject.tertiaryObject.quaternaryObject.title' => 'Quaternary Title',
                'object.secondaryObject.tertiaryObject.quaternaryObject.media' => [3, 4],
            ],
            [
                'uuid' => new Field\IdentifierField('uuid'),
                'object' => new Field\ObjectField('object', [
                    'title' => new Field\TextField('title'),
                    'secondaryObject' => new Field\ObjectField('secondaryObject', [
                        'title' => new Field\TextField('title'),
                        'tertiaryObject' => new Field\ObjectField('tertiaryObject', [
                            'title' => new Field\TextField('title'),
                            'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                            'quaternaryObject' => new Field\ObjectField('quaternaryObject', [
                                'title' => new Field\TextField('title'),
                                'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                            ]),
                        ]),
                    ]),
                ]),
            ],
        ];

        yield 'nested_object_multiple' => [
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'object' => [
                    'title' => 'Title',
                    'secondaryObject' => [
                        [
                            'title' => 'Secondary Title',
                            'tertiaryObject' => [
                                'title' => 'Tertiary Title',
                                'media' => [1, 2],
                                'quaternaryObject' => [
                                    'title' => 'Quaternary Title',
                                    'media' => [3, 4],
                                ],
                            ],
                        ],
                        [
                            'title' => null,
                            'tertiaryObject' => [
                                'title' => null,
                                'quaternaryObject' => [
                                    'title' => null,
                                ],
                            ],
                        ],
                        [
                            'title' => 'Secondary Title 3',
                            'tertiaryObject' => [
                                'title' => 'Tertiary Title 3',
                                'media' => [6, 7],
                                'quaternaryObject' => [
                                    'title' => 'Quaternary Title 3',
                                    'media' => [8, 9],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'object.title' => 'Title',
                'object.secondaryObject.title' => ['Secondary Title', null, 'Secondary Title 3'],
                'object.secondaryObject.tertiaryObject.title' => ['Tertiary Title', null, 'Tertiary Title 3'],
                'object.secondaryObject.tertiaryObject.media' => [1, 2, 6, 7],
                'object.secondaryObject.tertiaryObject.quaternaryObject.title' => ['Quaternary Title', null, 'Quaternary Title 3'],
                'object.secondaryObject.tertiaryObject.quaternaryObject.media' => [3, 4, 8, 9],
            ],
            [
                'uuid' => new Field\IdentifierField('uuid'),
                'object' => new Field\ObjectField('object', [
                    'title' => new Field\TextField('title'),
                    'secondaryObject' => new Field\ObjectField('secondaryObject', [
                        'title' => new Field\TextField('title'),
                        'tertiaryObject' => new Field\ObjectField('tertiaryObject', [
                            'title' => new Field\TextField('title'),
                            'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                            'quaternaryObject' => new Field\ObjectField('quaternaryObject', [
                                'title' => new Field\TextField('title'),
                                'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                            ]),
                        ]),
                    ], multiple: true),
                ]),
            ],
        ];

        yield 'nested_types_multiple' => [
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'blocks' => [
                    [
                        'type' => 'text',
                        'title' => 'Titel',
                        'description' => '<p>Description</p>',
                        'media' => [3, 4],
                        'secondaryBlocks' => [
                            [
                                'type' => 'text',
                                'title' => 'Titel',
                                'description' => '<p>Description</p>',
                                'media' => [3, 4],
                            ],
                            [
                                'type' => 'text',
                                'title' => 'Titel 2',
                                'description' => null,
                            ],
                            [
                                'type' => 'embed',
                                'title' => 'Video',
                                'media' => 'https://www.youtube.com/watch?v=iYM2zFP3Zn0',
                            ],
                            [
                                'type' => 'text',
                                'title' => 'Titel 4',
                                'description' => '<p>Description 4</p>',
                                'media' => [3, 4],
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'title' => 'Titel 2',
                        'description' => null,
                    ],
                    [
                        'type' => 'embed',
                        'title' => 'Video',
                        'media' => 'https://www.youtube.com/watch?v=iYM2zFP3Zn0',
                    ],
                    [
                        'type' => 'text',
                        'title' => 'Titel 4',
                        'description' => '<p>Description 4</p>',
                        'media' => [3, 4],
                        'secondaryBlocks' => [
                            [
                                'type' => 'text',
                                'title' => 'Titel',
                                'description' => '<p>Description</p>',
                                'media' => [3, 4],
                            ],
                            [
                                'type' => 'text',
                                'title' => 'Titel 2',
                                'description' => null,
                            ],
                            [
                                'type' => 'embed',
                                'title' => 'Video',
                                'media' => 'https://www.youtube.com/watch?v=iYM2zFP3Zn0',
                            ],
                            [
                                'type' => 'text',
                                'title' => 'Titel 4',
                                'description' => '<p>Description 4</p>',
                                'media' => [3, 4],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'blocks.text.title' => ['Titel', 'Titel 2', 'Titel 4'],
                'blocks.text.description' => ['<p>Description</p>', null, '<p>Description 4</p>'],
                'blocks.text.media' => [3, 4, 3, 4],
                'blocks.text.secondaryBlocks.text.title' => ['Titel', 'Titel 2', 'Titel 4', 'Titel', 'Titel 2', 'Titel 4'],
                'blocks.text.secondaryBlocks.text.description' => ['<p>Description</p>', null, '<p>Description 4</p>', '<p>Description</p>', null, '<p>Description 4</p>'],
                'blocks.text.secondaryBlocks.text.media' => [3, 4, 3, 4, 3, 4, 3, 4],
                'blocks.text.secondaryBlocks.embed.title' => ['Video', 'Video'],
                'blocks.text.secondaryBlocks.embed.media' => ['https://www.youtube.com/watch?v=iYM2zFP3Zn0', 'https://www.youtube.com/watch?v=iYM2zFP3Zn0'],
                'blocks.embed.title' => ['Video'],
                'blocks.embed.media' => ['https://www.youtube.com/watch?v=iYM2zFP3Zn0'],
            ],
            [
                'uuid' => new Field\IdentifierField('uuid'),
                'blocks' => new Field\TypedField('blocks', 'type', [
                    'text' => [
                        'title' => new Field\TextField('title'),
                        'description' => new Field\TextField('description'),
                        'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                        'secondaryBlocks' => new Field\TypedField('secondaryBlocks', 'type', [
                            'text' => [
                                'title' => new Field\TextField('title'),
                                'description' => new Field\TextField('description'),
                                'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                            ],
                            'embed' => [
                                'title' => new Field\TextField('title'),
                                'media' => new Field\TextField('media', searchable: false),
                            ],
                        ], multiple: true),
                    ],
                    'embed' => [
                        'title' => new Field\TextField('title'),
                        'media' => new Field\TextField('media', searchable: false),
                    ],
                ], multiple: true),
            ],
        ];

        yield 'nested_objects_multiple' => [
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'blocks' => [
                    [
                        'title' => 'Titel',
                        'description' => '<p>Description</p>',
                        'media' => [3, 4],
                        'secondaryBlocks' => [
                            [
                                'title' => 'Titel',
                                'description' => '<p>Description</p>',
                                'media' => [3, 4],
                            ],
                            [
                                'title' => 'Titel 2',
                                'description' => null,
                            ],
                            [
                                'title' => 'Titel 4',
                                'description' => '<p>Description 4</p>',
                                'media' => [3, 4],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Titel 2',
                        'description' => null,
                    ],
                    [
                        'title' => 'Titel 4',
                        'description' => '<p>Description 4</p>',
                        'media' => [3, 4],
                        'secondaryBlocks' => [
                            [
                                'title' => 'Titel',
                                'description' => '<p>Description</p>',
                                'media' => [3, 4],
                            ],
                            [
                                'title' => 'Titel 2',
                                'description' => null,
                            ],
                            [
                                'title' => 'Titel 4',
                                'description' => '<p>Description 4</p>',
                                'media' => [3, 4],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
                'blocks.title' => ['Titel', 'Titel 2', 'Titel 4'],
                'blocks.description' => ['<p>Description</p>', null, '<p>Description 4</p>'],
                'blocks.media' => [3, 4, 3, 4],
                'blocks.secondaryBlocks.title' => ['Titel', 'Titel 2', 'Titel 4', 'Titel', 'Titel 2', 'Titel 4'],
                'blocks.secondaryBlocks.description' => ['<p>Description</p>', null, '<p>Description 4</p>', '<p>Description</p>', null, '<p>Description 4</p>'],
                'blocks.secondaryBlocks.media' => [3, 4, 3, 4, 3, 4, 3, 4],
            ],
            [
                'uuid' => new Field\IdentifierField('uuid'),
                'blocks' => new Field\ObjectField('blocks', [
                    'title' => new Field\TextField('title'),
                    'description' => new Field\TextField('description'),
                    'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                    'secondaryBlocks' => new Field\ObjectField('secondaryBlocks', [
                        'title' => new Field\TextField('title'),
                        'description' => new Field\TextField('description'),
                        'media' => new Field\IntegerField('media', multiple: true, searchable: false),
                    ], multiple: true),
                ], multiple: true),
            ],
        ];
    }
}
