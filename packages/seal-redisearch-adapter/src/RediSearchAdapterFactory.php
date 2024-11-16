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

namespace CmsIg\Seal\Adapter\RediSearch;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

/**
 * @experimental
 */
class RediSearchAdapterFactory implements AdapterFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface|null $container = null,
    ) {
    }

    public function createAdapter(array $dsn): AdapterInterface
    {
        $client = $this->createClient($dsn);

        return new RediSearchAdapter($client);
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
    public function createClient(array $dsn): \Redis
    {
        if ('' === $dsn['host']) {
            $client = $this->container?->get(\Redis::class);

            if (!$client instanceof \Redis) {
                throw new \InvalidArgumentException('Unknown Redis client.');
            }

            return $client;
        }

        $user = $dsn['user'] ?? '';
        $password = $dsn['pass'] ?? '';
        $password = '' !== $password ? [$user, $password] : $user;

        $client = new \Redis();
        $client->pconnect($dsn['host'], $dsn['port'] ?? 6379);

        if ($password) {
            $client->auth($password);
        }

        return $client;
    }

    public static function getName(): string
    {
        return 'redis';
    }
}
