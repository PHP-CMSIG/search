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
     *     query: array<string, mixed>,
     * } $dsn
     */
    public function createHelper(array $dsn): LoupeHelper
    {
        /** @var LoupeFactory $loupeFactory */
        $loupeFactory = $this->container?->has(LoupeFactory::class)
            ? $this->container->get(LoupeFactory::class)
            : new LoupeFactory();

        $directory = $dsn['host'] . ($dsn['path'] ?? '');
        $configurationQuery = $dsn['query']['configuration'] ?? null;
        if (!\is_array($configurationQuery)) {
            if (null === $configurationQuery) {
                $configurationQuery = [];
            } else {
                throw new \InvalidArgumentException('The "configuration" query param must be an array of strings (e.g. configuration[*]=...&configuration[blog]=...).');
            }
        }

        return new LoupeHelper(
            $loupeFactory,
            $directory,
            $this->parseIndexConfigurations($configurationQuery, 'configuration'),
        );
    }

    /**
     * @param array<mixed> $indexConfigurationQuery
     *
     * @return array<string, Configuration>
     */
    private function parseIndexConfigurations(array $indexConfigurationQuery, string $queryParam): array
    {
        $indexConfigurations = [];

        foreach ($indexConfigurationQuery as $indexName => $indexConfigurationString) {
            if (!\is_string($indexName)) {
                throw new \InvalidArgumentException(\sprintf('The "%s" query param array keys must be strings.', $queryParam));
            }

            if (!\is_string($indexConfigurationString)) {
                throw new \InvalidArgumentException(\sprintf('The "%s[%s]" query param must be a string.', $queryParam, $indexName));
            }

            try {
                $indexConfigurations[$indexName] = Configuration::fromString($indexConfigurationString);
            } catch (\JsonException $exception) {
                throw new \InvalidArgumentException(\sprintf('The "%s[%s]" query param must contain a Loupe\\Loupe\\Configuration string.', $queryParam, $indexName), 0, $exception);
            }
        }

        return $indexConfigurations;
    }

    public static function getName(): string
    {
        return 'loupe';
    }
}
