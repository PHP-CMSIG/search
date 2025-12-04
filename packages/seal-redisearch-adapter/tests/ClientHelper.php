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

namespace CmsIg\Seal\Adapter\RediSearch\Tests;

final class ClientHelper
{
    private static \Redis|null $client = null;

    public static function getClient(): \Redis
    {
        if (!self::$client instanceof \Redis) {
            $redisHost = $_ENV['REDIS_HOST'] ?? '127.0.0.1:6379';
            \assert(\is_string($redisHost), 'REDIS_HOST must be a string.');

            $redisPassword = $_ENV['REDIS_PASSWORD'] ?? '';
            \assert(\is_string($redisPassword), 'REDIS_PASSWORD must be a string.');

            [$host, $port] = \explode(':', $redisHost);

            self::$client = new \Redis();
            self::$client->pconnect($host, (int) $port);

            if ('' !== $redisPassword) {
                self::$client->auth($redisPassword);
            }
        }

        return self::$client;
    }
}
