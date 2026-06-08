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

namespace CmsIg\Seal\Adapter\Opensearch;

use OpenSearch\RequestFactory;
use OpenSearch\RequestFactoryInterface;
use OpenSearch\Serializers\SmartSerializer;
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class BaseUriRequestFactory implements RequestFactoryInterface
{
    private readonly RequestFactory $requestFactory;

    private readonly string|null $authorizationHeader;

    public function __construct(
        private readonly string $baseUri,
        PsrRequestFactoryInterface $psrRequestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        string $username = '',
        string $password = '',
    ) {
        $this->requestFactory = new RequestFactory(
            $psrRequestFactory,
            $streamFactory,
            $uriFactory,
            new SmartSerializer(),
        );
        $this->authorizationHeader = '' !== $username || '' !== $password
            ? 'Basic ' . \base64_encode($username . ':' . $password)
            : null;
    }

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
    public static function fromDsn(
        array $dsn,
        PsrRequestFactoryInterface $psrRequestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
    ): self {
        $tlsQuery = $dsn['query']['tls'] ?? 'false';
        \assert(\is_string($tlsQuery), 'The "tls" query param must be a string.');
        $useTls = \filter_var($tlsQuery, \FILTER_VALIDATE_BOOLEAN, \FILTER_REQUIRE_SCALAR);
        $scheme = $useTls ? 'https' : 'http';
        $port = $dsn['port'] ?? ($useTls ? 443 : 9200);
        $path = isset($dsn['path']) ? \rtrim($dsn['path'], '/') : '';

        return new self(
            $scheme . '://' . $dsn['host'] . ':' . $port . $path,
            $psrRequestFactory,
            $streamFactory,
            $uriFactory,
            $dsn['user'] ?? '',
            $dsn['pass'] ?? '',
        );
    }

    public function createRequest(
        string $method,
        string $uri,
        array $params = [],
        string|array|null $body = null,
        array $headers = [],
    ): RequestInterface {
        $headers += [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (null !== $this->authorizationHeader) {
            $headers['Authorization'] = $this->authorizationHeader;
        }

        return $this->requestFactory->createRequest(
            $method,
            $this->baseUri . $uri,
            $params,
            $body,
            $headers,
        );
    }
}
