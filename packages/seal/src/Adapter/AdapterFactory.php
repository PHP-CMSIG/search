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

namespace CmsIg\Seal\Adapter;

/**
 * @experimental
 */
final class AdapterFactory
{
    /**
     * @var array<string, AdapterFactoryInterface>
     */
    private array $factories;

    /**
     * @param iterable<string, AdapterFactoryInterface> $factories
     */
    public function __construct(
        iterable $factories,
    ) {
        $this->factories = [...$factories];
    }

    /**
     * @internal
     *
     * @return array{
     *     scheme: string,
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     path?: string,
     *     query: array<string, string>,
     *     fragment?: string,
     * }
     */
    public function parseDsn(string $dsn): array
    {
        /** @var string|null $adapterName */
        $adapterName = \explode(':', $dsn, 2)[0];

        if (!$adapterName) {
            throw new \InvalidArgumentException(
                'Invalid DSN: "' . $dsn . '".',
            );
        }

        if (!isset($this->factories[$adapterName])) {
            throw new \InvalidArgumentException(
                'Unknown Search adapter: "' . $adapterName . '" available adapters are "' . \implode('", "', \array_keys($this->factories)) . '".',
            );
        }

        /**
         * @var array{
         *     scheme: string,
         *     host: string,
         *     port?: int,
         *     user?: string,
         *     pass?: string,
         *     path?: string,
         *     query?: string,
         *     fragment?: string,
         * }|false $parsedDsn
         */
        $parsedDsn = \parse_url($dsn);

        // make DSN like algolia://YourApplicationID:YourAdminAPIKey parseable
        if (false === $parsedDsn) {
            $query = '';
            if (\str_contains($dsn, '?')) {
                [$dsn, $query] = \explode('?', $dsn);
                $query = '?' . $query;
            }

            $isWindowsLikePath = false;
            if (\str_contains($dsn, ':///')) {
                // make DSN like loupe:///full/path/project/var/indexes parseable
                $dsn = \str_replace(':///', '://' . $adapterName . '/', $dsn . $query);
            } elseif (':\\' === \substr($dsn, \strlen($adapterName) + 4, 2)) { // detect windows like path
                // make the DSN work with parseurl for URLs like loupe://C:\path\project\var\indexes
                $dsn = $adapterName . '://' . $adapterName . '/' . \rawurlencode(\substr($dsn, \strlen($adapterName) + 3)) . $query;
                $isWindowsLikePath = true;
            } else {
                $dsn = $dsn . '@' . $adapterName . $query;
            }

            /**
             * @var array{
             *     scheme: string,
             *     host: string,
             *     port?: int,
             *     user?: string,
             *     pass?: string,
             *     path?: string,
             *     query?: string,
             *     fragment?: string,
             * } $parsedDsn
             */
            $parsedDsn = \parse_url($dsn);

            if ($isWindowsLikePath && isset($parsedDsn['path'])) {
                $parsedDsn['path'] = \rawurldecode(\ltrim($parsedDsn['path'], '/'));
            }
            $parsedDsn['host'] = '';
        }

        /** @var array<string, string> $query */
        $query = [];
        if (isset($parsedDsn['query'])) {
            \parse_str($parsedDsn['query'], $query);
        }

        $parsedDsn['query'] = $query;

        /**
         * @var array{
         *     scheme: string,
         *     host: string,
         *     port?: int,
         *     user?: string,
         *     pass?: string,
         *     path?: string,
         *     query: array<string, string>,
         *     fragment?: string,
         * } $parsedDsn
         */

        return $parsedDsn;
    }

    public function createAdapter(string $dsn): AdapterInterface
    {
        $parsedDsn = $this->parseDsn($dsn);

        return $this->factories[$parsedDsn['scheme']]->createAdapter($parsedDsn);
    }
}
