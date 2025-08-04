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

namespace CmsIg\Seal\Adapter\MongoDB;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use MongoDB\Client;
use Psr\Container\ContainerInterface;

/**
 * @experimental
 */
class MongoDBAdapterFactory implements AdapterFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface|null $container = null,
    ) {
    }

    public function createAdapter(array $dsn): AdapterInterface
    {
        $client = $this->createClient($dsn);

        return new MongoDBAdapter($client);
    }

    /**
     * @internal
     *
     * @param array{
     *     scheme: string,
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     path?: string,
     *     query: array<string, string>,
     *     fragment?: string,
     * } $dsn
     */
    public function createClient(array $dsn): ClientWrapper
    {
        if ('' === $dsn['host']) {
            $client = $this->container?->get(Client::class);

            if (!$client instanceof Client) {
                throw new \InvalidArgumentException('Unknown MongoDB client.');
            }

            return $client;
        }

        $dsnUri = 'mongodb://';

        if (isset($dsn['user']) || isset($dsn['pass'])) {
            $dsnUri .= $dsn['user'] . ':' . $dsn['pass'] . '@';
        }

        $dsnUri .= $dsn['host'];
        if (isset($dsn['port'])) {
            $dsnUri .= ':' . $dsn['port'];
        }

        \assert(!isset($dsn['path']), 'A selected database is required.');
        $databaseName = \ltrim($dsn['path'] ?? 'default', '/');

        if (isset($dsn['query'])) {
            $dsnUri .= '?' . \http_build_query($dsn['query']);
        }
        if (isset($dsn['fragment'])) {
            $dsnUri .= '#' . $dsn['fragment'];
        }

        return new ClientWrapper(new Client($dsnUri), $databaseName);
    }

    public static function getName(): string
    {
        return 'mongodb';
    }
}
