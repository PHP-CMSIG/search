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

return new Index('blog', [
    'title' => new Field\TextField('title'),
    'description' => new Field\TextField('description', distinct: true, facet: true, options: ['option2' => true]),
    'blocks' => new Field\TypedField('blocks', 'type', [
        'text' => [
            'description' => new Field\TextField('description', distinct: true, facet: true),
        ],
        'gallery' => [
            'media' => new Field\TextField('media', multiple: true),
        ],
    ], multiple: true),
    'rating' => new Field\IntegerField('rating', distinct: true, facet: true),
    'price' => new Field\FloatField('price', distinct: true, facet: true),
    'created' => new Field\DateTimeField('created', distinct: true, facet: true),
    'published' => new Field\BooleanField('published', multiple: true, filterable: true, sortable: true, distinct: true, facet: true, options: ['nested' => ['replace' => 'new']]),
    'archived' => new Field\BooleanField('archived'),
    'footerText' => new Field\TextField('footerText'),
], [
    'simpleOption' => 'simpleOption-overwrite',
    'nestedOption' => [
        'key1' => 'value1-overwrite',
        'key3' => 'value3-new',
    ],
]);
