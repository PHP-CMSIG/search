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
    'flags' => new Index(
        name: 'flags',
        fields: [
            'uuid' => new Field\IdentifierField(
                name: 'uuid',
            ),
            'text' => new Field\TextField(
                name: 'text',
                searchable: false,
                filterable: true,
                sortable: true,
                distinct: true,
                facet: true,
                options: ['customKey' => 'customValue'],
            ),
            'commentsCount' => new Field\IntegerField(
                name: 'commentsCount',
                filterable: true,
                sortable: true,
                distinct: true,
                facet: true,
                options: ['customKey' => 'customValue'],
            ),
            'rating' => new Field\FloatField(
                name: 'rating',
                filterable: true,
                sortable: true,
                distinct: true,
                facet: true,
                options: ['customKey' => 'customValue'],
            ),
            'isSpecial' => new Field\BooleanField(
                name: 'isSpecial',
                filterable: true,
                sortable: true,
                distinct: true,
                facet: true,
                options: ['customKey' => 'customValue'],
            ),
            'created' => new Field\DateTimeField(
                name: 'created',
                filterable: true,
                sortable: true,
                distinct: true,
                facet: true,
                options: ['customKey' => 'customValue'],
            ),
        ],
        options: [
            'odm' => [
                'class' => \CmsIg\Seal\Odm\Tests\Schema\Fixtures\Flags\Flags::class,
            ],
        ],
    ),
]);
