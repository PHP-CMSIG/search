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

namespace CmsIg\Seal\Adapter\Memory;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;

/**
 * @experimental
 */
class MemoryAdapterFactory implements AdapterFactoryInterface
{
    public function createAdapter(array $dsn): AdapterInterface
    {
        return new MemoryAdapter();
    }

    public static function getName(): string
    {
        return 'memory';
    }
}
