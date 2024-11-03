<?php

declare(strict_types=1);

/*
 * This file is part of the Schranz Search package.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Schranz\Search\SEAL\Adapter\AdapterFactory;
use Schranz\Search\SEAL\Adapter\AdapterFactoryInterface;
use Schranz\Search\SEAL\Adapter\AdapterInterface;

#[CoversClass(AdapterFactory::class)]
class AdapterFactoryTest extends TestCase
{
    private AdapterFactory $adapterFactory;

    protected function setUp(): void
    {
        $this->adapterFactory = new AdapterFactory([
            'scheme' => $this->createAdapterFactory('scheme'),
        ]);
    }

    /**
     * @param array{
     *     scheme: string,
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     path?: string,
     *     query: array<string, string>,
     *     fragment?: string,
     * } $expectedResult
     */
    #[DataProvider('provideDsn')]
    public function testParseDsn(string $dsn, array $expectedResult): void
    {
        $parsedDsn = $this->adapterFactory->parseDsn($dsn);

        $this->assertSame($expectedResult, $parsedDsn);
    }

    public function testAdapterNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown Search adapter: "not-found" available adapters are "scheme".');

        $this->adapterFactory->parseDsn('not-found://host:1234');
    }

    public function testInvalidDsn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid DSN: "".');

        $this->adapterFactory->parseDsn('');
    }

    /**
     * @return \Generator<array{0: string, 1: array{
     *      scheme: string,
     *      host: string,
     *      port?: int,
     *      user?: string,
     *      pass?: string,
     *      path?: string,
     *      query: array<string, string>,
     *      fragment?: string,
     *  }}>
     */
    public static function provideDsn(): \Generator
    {
        yield 'standard' => [
            'scheme://host:1234',
            [
                'scheme' => 'scheme',
                'host' => 'host',
                'port' => 1234,
                'query' => [],
            ],
        ];

        yield 'standard_full' => [
            'scheme://user:pass@host:1234/path?queryKey=queryValue#fragment',
            [
                'scheme' => 'scheme',
                'host' => 'host',
                'port' => 1234,
                'user' => 'user',
                'pass' => 'pass',
                'path' => '/path',
                'query' => [
                    'queryKey' => 'queryValue',
                ],
                'fragment' => 'fragment',
            ],
        ];

        yield 'only_username_password' => [
            'scheme://user:pass',
            [
                'scheme' => 'scheme',
                'host' => '',
                'user' => 'user',
                'pass' => 'pass',
                'query' => [],
            ],
        ];

        yield 'only_username_password_with_query' => [
            'scheme://user:pass?queryKey=queryValue#fragment',
            [
                'scheme' => 'scheme',
                'host' => '',
                'user' => 'user',
                'pass' => 'pass',
                'query' => [
                    'queryKey' => 'queryValue',
                ],
                'fragment' => 'fragment',
            ],
        ];

        yield 'path' => [
            'scheme:///var/data/directory',
            [
                'scheme' => 'scheme',
                'host' => '',
                'path' => '/var/data/directory',
                'query' => [],
            ],
        ];

        yield 'path_with_query' => [
            'scheme:///var/data/directory?queryKey=queryValue#fragment',
            [
                'scheme' => 'scheme',
                'host' => '',
                'path' => '/var/data/directory',
                'query' => [
                    'queryKey' => 'queryValue',
                ],
                'fragment' => 'fragment',
            ],
        ];

        yield 'windows_path' => [
            'scheme://C:\path\project\var',
            [
                'scheme' => 'scheme',
                'host' => '',
                'path' => 'C:\path\project\var',
                'query' => [],
            ],
        ];

        yield 'windows_path_with_query' => [
            'scheme://C:\path\project\var?queryKey=queryValue#fragment',
            [
                'scheme' => 'scheme',
                'host' => '',
                'path' => 'C:\path\project\var',
                'query' => [
                    'queryKey' => 'queryValue',
                ],
                'fragment' => 'fragment',
            ],
        ];
    }

    private function createAdapterFactory(string $name): AdapterFactoryInterface
    {
        $adapterFactory = new class() implements AdapterFactoryInterface {
            public static string $name;

            public function createAdapter(array $dsn): AdapterInterface
            {
                throw new \Exception('Not implemented');
            }

            public static function getName(): string
            {
                return static::$name;
            }
        };

        $adapterFactory::$name = $name;

        return $adapterFactory;
    }
}
