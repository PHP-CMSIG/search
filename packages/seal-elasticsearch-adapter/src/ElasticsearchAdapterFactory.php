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

namespace CmsIg\Seal\Adapter\Elasticsearch;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\ClientInterface;
use Psr\Container\ContainerInterface;

/**
 * @experimental
 */
class ElasticsearchAdapterFactory implements AdapterFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface|null $container = null,
    ) {
    }

    public function createAdapter(array $dsn): AdapterInterface
    {
        $client = $this->createClient($dsn);

        return new ElasticsearchAdapter($client);
    }

    /**
     * @internal
     *
     * @param array{
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     * } $dsn
     */
    public function createClient(array $dsn): Client
    {
        if ('' === $dsn['host']) {
            $client = $this->container?->get(ClientInterface::class);

            if (!$client instanceof Client) {
                throw new \InvalidArgumentException('Unknown Elasticsearch client.');
            }

            return $client;
        }

        $client = ClientBuilder::create()->setHosts([
            $dsn['host'] . ':' . ($dsn['port'] ?? 9200),
        ]);

        $user = $dsn['user'] ?? '';
        $pass = $dsn['pass'] ?? '';

        if ($user || $pass) {
            $client->setBasicAuthentication($user, $pass);
        }

        return $client->build();
    }

    public static function getName(): string
    {
        return 'elasticsearch';
    }
}
