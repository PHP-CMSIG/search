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

use CmsIg\Seal\Marshaller\Marshaller;
use CmsIg\Seal\Testing\TestingHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Marshaller::class)]
class MarshallerTest extends TestCase
{
    public function testMarshall(): void
    {
        $marshaller = new Marshaller();
        $complexIndex = TestingHelper::createSchema()->indexes[TestingHelper::INDEX_COMPLEX];
        $documents = TestingHelper::createComplexFixtures();

        $rawDocument = $marshaller->marshall($complexIndex->fields, $documents[0]);

        $this->assertSame($this->getRawDocument(), $rawDocument);
    }

    public function testUnmarshall(): void
    {
        $marshaller = new Marshaller();
        $complexIndex = TestingHelper::createSchema()->indexes[TestingHelper::INDEX_COMPLEX];

        $document = $marshaller->unmarshall($complexIndex->fields, $this->getRawDocument());

        $documents = TestingHelper::createComplexFixtures();
        $this->assertSame($documents[0], $document);
    }

    public function testMarshallDateAsInt(): void
    {
        $marshaller = new Marshaller(dateAsInteger: true);
        $complexIndex = TestingHelper::createSchema()->indexes[TestingHelper::INDEX_COMPLEX];
        $documents = TestingHelper::createComplexFixtures();

        $rawDocument = $marshaller->marshall($complexIndex->fields, $documents[0]);

        $this->assertSame($this->getRawDocument(dateAsInteger: true), $rawDocument);
    }

    public function testUnmarshallDateAsInt(): void
    {
        $marshaller = new Marshaller(dateAsInteger: true);
        $complexIndex = TestingHelper::createSchema()->indexes[TestingHelper::INDEX_COMPLEX];

        $document = $marshaller->unmarshall($complexIndex->fields, $this->getRawDocument(dateAsInteger: true));

        $documents = TestingHelper::createComplexFixtures();
        $this->assertSame($documents[0], $document);
    }

    /**
     * @return array<string, mixed>
     */
    private function getRawDocument(bool $dateAsInteger = false): array
    {
        return [
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
                        'title' => 'Titel',
                        'description' => '<p>Description</p>',
                        'media' => [3, 4],
                    ],
                    [
                        '_originalIndex' => 1,
                        'title' => 'Titel 2',
                    ],
                    [
                        '_originalIndex' => 3,
                        'title' => 'Titel 4',
                        'description' => '<p>Description 4</p>',
                        'media' => [3, 4],
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
            'created' => $dateAsInteger ? 1_643_022_000 : '2022-01-24T12:00:00+01:00',
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
        ];
    }
}
