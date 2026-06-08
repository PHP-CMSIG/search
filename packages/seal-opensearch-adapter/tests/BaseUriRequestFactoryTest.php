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

namespace CmsIg\Seal\Adapter\Opensearch\Tests;

use CmsIg\Seal\Adapter\Opensearch\BaseUriRequestFactory;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BaseUriRequestFactory::class)]
final class BaseUriRequestFactoryTest extends TestCase
{
    /**
     * @param array{
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     path?: string,
     *     query: array<string, string|string[]>,
     * } $dsn
     */
    #[DataProvider('provideDsn')]
    public function testCreateRequestFromDsn(
        array $dsn,
        string $expectedUri,
        bool $expectsAuthorizationHeader,
    ): void {
        $httpFactory = new HttpFactory();
        $requestFactory = BaseUriRequestFactory::fromDsn(
            $dsn,
            $httpFactory,
            $httpFactory,
            $httpFactory,
        );

        $request = $requestFactory->createRequest('GET', '/_search');

        $this->assertSame($expectedUri, (string) $request->getUri());
        $this->assertSame($expectsAuthorizationHeader, $request->hasHeader('Authorization'));
    }

    public function testCreateRequestUsesBaseUriAndDefaultHeaders(): void
    {
        $httpFactory = new HttpFactory();
        $requestFactory = new BaseUriRequestFactory(
            'http://127.0.0.1:9200',
            $httpFactory,
            $httpFactory,
            $httpFactory,
        );

        $request = $requestFactory->createRequest(
            'GET',
            '/_search',
            ['pretty' => true, 'routing' => 'de'],
        );

        $this->assertSame(
            'http://127.0.0.1:9200/_search?pretty=true&routing=de',
            (string) $request->getUri(),
        );
        $this->assertSame(['application/json'], $request->getHeader('Accept'));
        $this->assertSame(['application/json'], $request->getHeader('Content-Type'));
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    public function testCreateRequestAddsBasicAuthorizationHeader(): void
    {
        $httpFactory = new HttpFactory();
        $requestFactory = new BaseUriRequestFactory(
            'https://opensearch.example:443',
            $httpFactory,
            $httpFactory,
            $httpFactory,
            'user',
            'pass',
        );

        $request = $requestFactory->createRequest(
            'POST',
            '/test-index/_doc/1',
            body: ['title' => 'Example'],
        );

        $this->assertSame(
            'https://opensearch.example/test-index/_doc/1',
            (string) $request->getUri(),
        );
        $this->assertSame(['Basic dXNlcjpwYXNz'], $request->getHeader('Authorization'));
        $this->assertSame('{"title":"Example"}', (string) $request->getBody());
    }

    /**
     * @return \Generator<string, array{
     *     0: array{
     *         host: string,
     *         port?: int,
     *         user?: string,
     *         pass?: string,
     *         path?: string,
     *         query: array<string, string|string[]>,
     *     },
     *     1: string,
     *     2: bool,
     * }>
     */
    public static function provideDsn(): \Generator
    {
        yield 'http default port' => [
            [
                'host' => '127.0.0.1',
                'query' => [],
            ],
            'http://127.0.0.1:9200/_search',
            false,
        ];

        yield 'https explicit tls' => [
            [
                'host' => '127.0.0.1',
                'query' => ['tls' => 'true'],
            ],
            'https://127.0.0.1/_search',
            false,
        ];

        yield 'https explicit port and auth' => [
            [
                'host' => 'opensearch.example',
                'port' => 9443,
                'user' => 'user',
                'pass' => 'pass',
                'query' => ['tls' => 'true'],
            ],
            'https://opensearch.example:9443/_search',
            true,
        ];

        yield 'http with path prefix' => [
            [
                'host' => 'example.com',
                'path' => '/opensearch',
                'query' => [],
            ],
            'http://example.com:9200/opensearch/_search',
            false,
        ];
    }
}
