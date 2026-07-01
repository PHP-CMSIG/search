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

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;

return new Schema([
    'blog' => new Index(
        name: 'blog',
        fields: [
            'id' => new Field\IdentifierField(
                name: 'id',
                options: [
                    'odm' => [
                        'type' => 'int',
                    ],
                ],
            ),
            'text' => new Field\TextField(
                name: 'text',
            ),
        ],
        options: [
            'odm' => [
                'class' => \CmsIg\Seal\Odm\Tests\Schema\Fixtures\Basic\Blog::class,
            ],
        ],
    ),
    'news' => new Index(
        name: 'news',
        fields: [
            'uuid' => new Field\IdentifierField(
                name: 'uuid',
            ),
            'text' => new Field\TextField(
                name: 'text',
            ),
            'commentsCount' => new Field\IntegerField(
                name: 'commentsCount',
            ),
            'rating' => new Field\FloatField(
                name: 'rating',
            ),
            'isSpecial' => new Field\BooleanField(
                name: 'isSpecial',
            ),
            'created' => new Field\DateTimeField(
                name: 'created',
            ),
        ],
        options: [
            'odm' => [
                'class' => \CmsIg\Seal\Odm\Tests\Schema\Fixtures\Basic\News::class,
            ],
        ],
    ),
]);
