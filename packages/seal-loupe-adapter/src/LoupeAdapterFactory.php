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

namespace CmsIg\Seal\Adapter\Loupe;

use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\LoupeFactory;
use Psr\Container\ContainerInterface;

/**
 * @experimental
 */
class LoupeAdapterFactory implements AdapterFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface|null $container = null,
    ) {
    }

    public function createAdapter(array $dsn): AdapterInterface
    {
        $helper = $this->createHelper($dsn);

        return new LoupeAdapter($helper);
    }

    /**
     * @internal
     *
     * @param array{
     *     host: string,
     *     path?: string,
     *     query: array<string, string|string[]>,
     * } $dsn
     */
    public function createHelper(array $dsn): LoupeHelper
    {
        /** @var LoupeFactory $loupeFactory */
        $loupeFactory = $this->container?->has(LoupeFactory::class)
            ? $this->container->get(LoupeFactory::class)
            : new LoupeFactory();

        $directory = $dsn['host'] . ($dsn['path'] ?? '');
        $configuration = null;
        $indexConfigurations = [];
        $configurationString = $dsn['query']['configuration'] ?? null;
        if (null !== $configurationString) {
            if (\is_array($configurationString)) {
                foreach ($configurationString as $indexName => $indexConfigurationString) {
                    \assert(\is_string($indexConfigurationString), 'The "configuration" query param values must be strings.');
                    try {
                        $indexConfigurations[$indexName] = Configuration::fromString($indexConfigurationString);
                    } catch (\JsonException $exception) {
                        throw new \InvalidArgumentException(\sprintf('The "configuration[%s]" query param must contain a Loupe\\Loupe\\Configuration string.', $indexName), 0, $exception);
                    }
                }

                \assert([] !== $indexConfigurations);
            } else {
                \assert(\is_string($configurationString), 'The "configuration" query param must be a string or an array of strings.');
                try {
                    $configuration = Configuration::fromString($configurationString);
                } catch (\JsonException $exception) {
                    throw new \InvalidArgumentException('The "configuration" query param must contain a Loupe\\Loupe\\Configuration string.', 0, $exception);
                }
            }
        }

        return new LoupeHelper(
            $loupeFactory,
            $directory,
            $indexConfigurations,
            $configuration,
        );
    }

    public static function getName(): string
    {
        return 'loupe';
    }
}
