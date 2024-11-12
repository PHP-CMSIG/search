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

namespace CmsIg\Seal\Adapter\Solr\Tests;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ClientHelper
{
    private static Client|null $client = null;

    public static function getClient(): Client
    {
        if (!self::$client instanceof Client) {
            [$host, $port] = \explode(':', $_ENV['SOLR_HOST'] ?? '127.0.0.1:8983');

            $adapter = new Curl();
            $adapter->setTimeout(30);
            $eventDispatcher = new EventDispatcher();
            $options = [
                'endpoint' => [
                    'localhost' => [
                        'host' => $host,
                        'port' => $port,
                    ],
                ],
            ];

            self::$client = new Client($adapter, $eventDispatcher, $options);
        }

        return self::$client;
    }
}
