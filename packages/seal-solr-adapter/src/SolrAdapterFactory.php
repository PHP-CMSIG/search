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

namespace CmsIg\Seal\Adapter\Solr;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Solarium\Client;
use Solarium\Core\Client\Adapter\AdapterInterface as ClientAdapterInterface;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Adapter\Psr18Adapter;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @experimental
 */
class SolrAdapterFactory implements AdapterFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface|null $container = null,
    ) {
    }

    public function createAdapter(array $dsn): AdapterInterface
    {
        $client = $this->createClient($dsn);

        return new SolrAdapter($client);
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
            $client = $this->container?->get(Client::class);

            if (!$client instanceof Client) {
                throw new \InvalidArgumentException('Unknown Solr client.');
            }

            return $client;
        }

        $adapter = $this->createClientAdapter();
        $eventDispatcher = $this->createEventDispatcher();
        $options = [
            'endpoint' => [
                'localhost' => \array_filter([
                    'host' => $dsn['host'],
                    'port' => $dsn['port'] ?? 8983,
                    'username' => $dsn['user'] ?? null,
                    'password' => $dsn['pass'] ?? null,
                ]),
            ],
        ];

        return new Client($adapter, $eventDispatcher, $options);
    }

    private function createEventDispatcher(): EventDispatcherInterface
    {
        if ($this->container?->has(EventDispatcherInterface::class)) {
            /** @var EventDispatcherInterface */
            return $this->container->get(EventDispatcherInterface::class);
        }

        return new EventDispatcher();
    }

    private function createClientAdapter(): ClientAdapterInterface
    {
        if ($this->container?->has(ClientInterface::class)) {
            return new Psr18Adapter(
                $this->container->get(ClientInterface::class), // @phpstan-ignore-line
                $this->container->get(RequestFactoryInterface::class), // @phpstan-ignore-line
                $this->container->get(StreamFactoryInterface::class), // @phpstan-ignore-line
            );
        }

        return new Curl();
    }

    public static function getName(): string
    {
        return 'solr';
    }
}
