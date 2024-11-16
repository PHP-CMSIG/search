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

namespace CmsIg\Seal\Adapter\Multi;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

/**
 * @experimental
 */
class MultiAdapterFactory implements AdapterFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $prefix = '',
    ) {
    }

    public function createAdapter(array $dsn): AdapterInterface
    {
        $adapters = $this->getAdapters($dsn);

        return new MultiAdapter($adapters);
    }

    /**
     * @internal
     *
     * @param array{
     *     host: string,
     *     query: array<string, string>,
     * } $dsn
     *
     * @return iterable<AdapterInterface>
     */
    public function getAdapters(array $dsn): iterable
    {
        /** @var string[] $adapterNames */
        $adapterNames = $dsn['query']['adapters'] ?? [];

        $adapterNames = \array_merge(\array_filter([$dsn['host']]), $adapterNames);
        foreach ($adapterNames as $adapterName) {
            /** @var AdapterInterface $adapter */
            $adapter = $this->container->get($this->prefix . $adapterName);

            yield $adapterName => $adapter;
        }
    }

    public static function getName(): string
    {
        return 'multi';
    }
}
