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

use CmsIg\Seal\Search\SearchBuilderFactory\FormSearchInputNormalizer;
use PHPUnit\Framework\TestCase;

class FormSearchInputNormalizerTest extends TestCase
{
    public function testNormalizesPostLikeInput(): void
    {
        $normalized = FormSearchInputNormalizer::normalize([
            'limit' => '20',
            'offset' => '10',
            'filters' => [
                [
                    'type' => 'or',
                    'conditions' => [
                        [
                            'type' => 'geoDistance',
                            'field' => 'location',
                            'latitude' => '45.472735',
                            'longitude' => '9.184019',
                            'distance' => '2000',
                        ],
                        [
                            'type' => 'and',
                            'conditions' => [
                                [
                                    'type' => 'geoBoundingBox',
                                    'field' => 'location',
                                    'northLatitude' => '45.494181',
                                    'eastLongitude' => '9.214024',
                                    'southLatitude' => '45.449484',
                                    'westLongitude' => '9.179175',
                                ],
                                [
                                    'type' => 'equal',
                                    'field' => 'tags',
                                    'value' => 'php',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(20, $normalized['limit']);
        $this->assertSame(10, $normalized['offset']);
        $this->assertSame(45.472735, $normalized['filters'][0]['conditions'][0]['latitude']);
        $this->assertSame(9.184019, $normalized['filters'][0]['conditions'][0]['longitude']);
        $this->assertSame(2000, $normalized['filters'][0]['conditions'][0]['distance']);
        $this->assertSame(45.494181, $normalized['filters'][0]['conditions'][1]['conditions'][0]['northLatitude']);
        $this->assertSame('php', $normalized['filters'][0]['conditions'][1]['conditions'][1]['value']);
    }

    public function testNormalizesEmptyLimitAsNull(): void
    {
        $normalized = FormSearchInputNormalizer::normalize([
            'limit' => '',
        ]);

        $this->assertNull($normalized['limit']);
    }

    public function testRejectsInvalidOffset(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Form value for "offset" must be an integer-compatible value.');

        FormSearchInputNormalizer::normalize([
            'offset' => 'abc',
        ]);
    }

    public function testRejectsInvalidGeoCoordinate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Form value for "filters[0].latitude" must be a numeric-compatible value.');

        FormSearchInputNormalizer::normalize([
            'filters' => [
                [
                    'type' => 'geoDistance',
                    'latitude' => 'abc',
                ],
            ],
        ]);
    }
}
