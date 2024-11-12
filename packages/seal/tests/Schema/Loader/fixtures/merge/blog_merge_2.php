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
    'description' => new Field\TextField('description', options: ['option2' => true]),
    'blocks' => new Field\TypedField('blocks', 'type', [
        'gallery' => [
            'media' => new Field\TextField('media', multiple: true),
        ],
    ], multiple: true),
    'footerText' => new Field\TextField('footerText'),
]);
