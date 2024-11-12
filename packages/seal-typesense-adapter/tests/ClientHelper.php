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

namespace CmsIg\Seal\Adapter\Typesense\Tests;

use Http\Discovery\HttpClientDiscovery;
use Typesense\Client;

final class ClientHelper
{
    private static Client|null $client = null;

    public static function getClient(): Client
    {
        if (!self::$client instanceof Client) {
            [$host, $port] = \explode(':', $_ENV['TYPESENSE_HOST'] ?? '127.0.0.1:8108');

            self::$client = new Client(
                [
                    'api_key' => $_ENV['TYPESENSE_API_KEY'],
                    'nodes' => [
                        [
                            'host' => $host,
                            'port' => $port,
                            'protocol' => 'http',
                        ],
                    ],
                    'client' => HttpClientDiscovery::find(),
                ],
            );
        }

        return self::$client;
    }
}
