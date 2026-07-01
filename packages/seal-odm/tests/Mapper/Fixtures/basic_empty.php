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

use CmsIg\Seal\Odm\Tests\Mapper\Fixtures\Schema\News;

return [
    'index' => 'news',
    'object' => new News(
        '019f1e56-714e-7ced-9675-96ad9eefd4bb',
    ),
    'document' => [
        'uuid' => '019f1e56-714e-7ced-9675-96ad9eefd4bb',
    ],
];
