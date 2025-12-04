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

namespace CmsIg\Seal\Adapter\Meilisearch\Tests;

use Meilisearch\Client;

final class ClientHelper
{
    private static Client|null $client = null;

    public static function getClient(): Client
    {
        if (!self::$client instanceof Client) {
            $meilisearchHost = $_ENV['MEILISEARCH_HOST'] ?? '127.0.0.1:7700';
            \assert(\is_string($meilisearchHost), 'MEILISEARCH_HOST must be a string.');

            self::$client = new Client($meilisearchHost);
        }

        return self::$client;
    }
}
