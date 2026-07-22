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

namespace CmsIg\Seal\Adapter\Algolia\Tests;

use Algolia\AlgoliaSearch\Api\SearchClient;
use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\Algolia\AlgoliaAdapterFactory;

final class ClientHelper
{
    private static SearchClient|null $client = null;

    public static function getClient(): SearchClient
    {
        if (!self::$client instanceof SearchClient) {
            if (!empty($_ENV['ALGOLIA_DSN'])) {
                $algoliaAdapterFactory = new AlgoliaAdapterFactory();
                $factory = new AdapterFactory([
                    'algolia' => $algoliaAdapterFactory,
                ]);

                \assert(\is_string($_ENV['ALGOLIA_DSN']), 'The "ALGOLIA_DSN" environment variable must be a string.');
                $parsedDsn = $factory->parseDsn(\trim($_ENV['ALGOLIA_DSN']));
                self::$client = $algoliaAdapterFactory->createClient($parsedDsn);
            } elseif (empty($_ENV['ALGOLIA_APPLICATION_ID']) || empty($_ENV['ALGOLIA_ADMIN_API_KEY'])) {
                throw new \InvalidArgumentException(
                    'The "ALGOLIA_APPLICATION_ID" and "ALGOLIA_ADMIN_API_KEY" environment variables need to be defined.',
                );
            } else {
                \assert(\is_string($_ENV['ALGOLIA_APPLICATION_ID']), 'The "ALGOLIA_APPLICATION_ID" environment variable must be a string.');
                \assert(\is_string($_ENV['ALGOLIA_ADMIN_API_KEY']), 'The "ALGOLIA_ADMIN_API_KEY" environment variable must be a string.');

                /** @var SearchClient $searchClient */
                $searchClient = SearchClient::create(
                    \trim($_ENV['ALGOLIA_APPLICATION_ID']),
                    \trim($_ENV['ALGOLIA_ADMIN_API_KEY']),
                );

                self::$client = $searchClient;
            }
        }

        return self::$client;
    }
}
