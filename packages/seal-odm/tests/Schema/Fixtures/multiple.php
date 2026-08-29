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
    'multiple' => new Index(
        name: 'multiple',
        fields: [
            'uuid' => new Field\IdentifierField(
                name: 'uuid',
            ),
            'texts' => new Field\TextField(
                name: 'texts',
                multiple: true,
            ),
            'commentsCounts' => new Field\IntegerField(
                name: 'commentsCounts',
                multiple: true,
            ),
            'ratings' => new Field\FloatField(
                name: 'ratings',
                multiple: true,
            ),
            'isSpecials' => new Field\BooleanField(
                name: 'isSpecials',
                multiple: true,
            ),
            'createds' => new Field\DateTimeField(
                name: 'createds',
                multiple: true,
            ),
        ],
        options: [
            'odm' => [
                'class' => \CmsIg\Seal\Odm\Tests\Schema\Fixtures\Multiple\Multiple::class,
            ],
        ],
    ),
    'multipleList' => new Index(
        name: 'multipleList',
        fields: [
            'uuid' => new Field\IdentifierField(
                name: 'uuid',
            ),
            'texts' => new Field\TextField(
                name: 'texts',
                multiple: true,
            ),
            'commentsCounts' => new Field\IntegerField(
                name: 'commentsCounts',
                multiple: true,
            ),
            'ratings' => new Field\FloatField(
                name: 'ratings',
                multiple: true,
            ),
            'isSpecials' => new Field\BooleanField(
                name: 'isSpecials',
                multiple: true,
            ),
            'createds' => new Field\DateTimeField(
                name: 'createds',
                multiple: true,
            ),
        ],
        options: [
            'odm' => [
                'class' => \CmsIg\Seal\Odm\Tests\Schema\Fixtures\Multiple\MultipleList::class,
            ],
        ],
    ),
]);
