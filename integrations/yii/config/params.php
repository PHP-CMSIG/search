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

use CmsIg\Seal\Integration\Yii\Command\IndexCreateCommand;
use CmsIg\Seal\Integration\Yii\Command\IndexDropCommand;
use CmsIg\Seal\Integration\Yii\Command\ReindexCommand;

return [
    'cmsig/seal-yii-module' => [
        'index_name_prefix' => '',
        'schemas' => [
            'app' => [
                'dir' => 'config/schemas',
            ],
        ],
        'engines' => [
            /* Example:
            'default' => [
                'adapter' => 'meilisearch://127.0.0.1:7700',
            ],
            */
        ],
        'reindex_providers' => [],
    ],
    'yiisoft/yii-console' => [
        'commands' => [
            'cmsig:seal:index-create' => IndexCreateCommand::class,
            'cmsig:seal:index-drop' => IndexDropCommand::class,
            'cmsig:seal:reindex' => ReindexCommand::class,
        ],
    ],
];
