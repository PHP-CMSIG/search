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

namespace CmsIg\Seal\Adapter\MongoDB\Tests;

use CmsIg\Seal\Adapter\MongoDB\ClientWrapper;
use MongoDB\Client;

final class ClientHelper
{
    private static ClientWrapper|null $client = null;

    public static function getClient(): ClientWrapper
    {
        if (!self::$client instanceof ClientWrapper) {
            self::$client = new ClientWrapper(new Client(
                $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017',
            ), $_ENV['MONGODB_DB'] ?? 'default');
        }

        return self::$client;
    }
}
