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

use CmsIg\Seal\Odm\Tests\Mapper\Fixtures\Schema\NestedObject;
use CmsIg\Seal\Odm\Tests\Mapper\Fixtures\Schema\NestedSubObject;

return [
    'index' => 'nested',
    'object' => new NestedObject(
        '019f1e56-714e-7ced-9675-96ad9eefd4bb',
        new NestedSubObject(
            'Some Text',
            4,
            3.5,
            true,
            new \DateTimeImmutable('2023-01-01 00:00:00'),
        ),
        [
            new NestedSubObject(
                'Some Text A',
                2,
                1.25,
                false,
                new \DateTimeImmutable('2025-12-08 12:12:12'),
            ),
            new NestedSubObject(
                'Some Text B',
                3,
                4.25,
                true,
                new \DateTimeImmutable('2026-12-12 13:13:13'),
            ),
        ],
    ),
    'document' => [
        'uuid' => '019f1e56-714e-7ced-9675-96ad9eefd4bb',
        'subObject' => [
            'text' => 'Some Text',
            'commentsCount' => 4,
            'rating' => 3.5,
            'isSpecial' => true,
            'created' => '2023-01-01 00:00:00',
        ],
        'subObjects' => [
            [
                'text' => 'Some Text A',
                'commentsCount' => 2,
                'rating' => 1.25,
                'isSpecial' => false,
                'created' => '2025-12-08 12:12:12',
            ],
            [
                'text' => 'Some Text B',
                'commentsCount' => 3,
                'rating' => 4.25,
                'isSpecial' => true,
                'created' => '2026-12-12 13:13:13',
            ],
        ],
    ],
];
