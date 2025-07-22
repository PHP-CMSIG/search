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

namespace CmsIg\Seal\Integration\Mezzio\Service;

use CmsIg\Seal\Schema\Loader\LoaderInterface;
use CmsIg\Seal\Schema\Loader\PhpFileLoader;

final class PhpFileLoaderProvider implements LoaderProviderInterface
{
    public function getLoader(string $engineName, array $config): LoaderInterface
    {
        $indexNamePrefix = $config['index_name_prefix'];

        $engineSchemaDirs = [];
        foreach ($config['schemas'] as $options) {
            $engineSchemaDirs[$options['engine'] ?? 'default'][] = $options['dir'];
        }

        $dirs = $engineSchemaDirs[$engineName] ?? [];

        return new PhpFileLoader($dirs, $indexNamePrefix);
    }
}
