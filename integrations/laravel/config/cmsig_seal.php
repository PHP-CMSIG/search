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

return [
    /*
    |--------------------------------------------------------------------------
    | Schema prefix
    |--------------------------------------------------------------------------
    |
    | Define the prefix used for the index names to avoid conflicts.
    */

    'index_name_prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Schema configs
    |--------------------------------------------------------------------------
    |
    | Define different directories for the schema loader.
    */

    'schemas' => [
        'app' => [
            'dir' => resource_path('schemas'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Engines
    |--------------------------------------------------------------------------
    |
    | Define different engines for the indexes.
    */

    'engines' => [
        'default' => [
            'adapter' => env(
                'ENGINE_URL',
                'meilisearch://127.0.0.1:7700',
            ),
        ],
    ],
];
