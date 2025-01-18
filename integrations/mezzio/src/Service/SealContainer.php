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

namespace CmsIg\Seal\Integration\Mezzio\Service;

use Psr\Container\ContainerInterface;

/**
 * @internal
 */
class SealContainer implements ContainerInterface
{
    /**
     * @param array<string|class-string, mixed> $services
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private array $services = [],
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|string $id
     *
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get($id): mixed
    {
        if (!isset($this->services[$id])) {
            if ($this->container->has($id)) {
                return $this->container->get($id);
            }

            throw new SealContainerNotFoundException($id);
        }

        return $this->services[$id];
    }

    /**
     * We need a setter for the services, because the AdapterFactories requiries the container but
     * need to have possibility to access other dynamically created adapters inside the Read-Write
     * and Multi adapter factories.
     *
     * @param class-string|string $id
     *
     * @internal
     */
    public function set(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }

    public function has($id): bool
    {
        return \array_key_exists($id, $this->services) || $this->container->has($id);
    }
}
