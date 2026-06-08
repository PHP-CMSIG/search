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

namespace CmsIg\Seal\Adapter\Opensearch\Tests;

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapterFactory;
use OpenSearch\Client;

final class ClientHelper
{
    private static Client|null $client = null;

    public static function getClient(): Client
    {
        if (!self::$client instanceof Client) {
            $host = $_ENV['OPENSEARCH_HOST'] ?? '127.0.0.1:9200';
            $host = \is_string($host) ? $host : '127.0.0.1:9200';

            $adapterFactory = new AdapterFactory([
                OpensearchAdapterFactory::getName() => new OpensearchAdapterFactory(),
            ]);

            self::$client = (new OpensearchAdapterFactory())->createClient(
                $adapterFactory->parseDsn(self::normalizeDsn($host)),
            );
        }

        return self::$client;
    }

    public static function normalizeDsn(string $host): string
    {
        if (\str_starts_with($host, 'opensearch://')) {
            return $host;
        }

        if (\str_starts_with($host, 'http://') || \str_starts_with($host, 'https://')) {
            $parsedHost = \parse_url($host);
            \assert(false !== $parsedHost, 'Expected OPENSEARCH_HOST to be a valid URL.');

            $dsn = 'opensearch://';

            if (isset($parsedHost['user'])) {
                $dsn .= $parsedHost['user'];

                if (isset($parsedHost['pass'])) {
                    $dsn .= ':' . $parsedHost['pass'];
                }

                $dsn .= '@';
            }

            $dsn .= $parsedHost['host'] ?? '';

            if (isset($parsedHost['port'])) {
                $dsn .= ':' . $parsedHost['port'];
            }

            $dsn .= $parsedHost['path'] ?? '';

            $query = [];
            if (isset($parsedHost['query'])) {
                \parse_str($parsedHost['query'], $query);
            }

            if ('https' === ($parsedHost['scheme'] ?? '')) {
                $query['tls'] = 'true';
            }

            if ([] !== $query) {
                $dsn .= '?' . \http_build_query($query);
            }

            return $dsn;
        }

        return 'opensearch://' . $host;
    }
}
