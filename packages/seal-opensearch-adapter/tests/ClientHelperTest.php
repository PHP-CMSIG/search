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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientHelperTest extends TestCase
{
    #[DataProvider('provideHosts')]
    public function testNormalizeDsn(string $host, string $expectedDsn): void
    {
        $this->assertSame($expectedDsn, ClientHelper::normalizeDsn($host));
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function provideHosts(): \Generator
    {
        yield 'host and port' => [
            '127.0.0.1:9200',
            'opensearch://127.0.0.1:9200',
        ];

        yield 'host and tls query' => [
            '127.0.0.1:9200?tls=true',
            'opensearch://127.0.0.1:9200?tls=true',
        ];

        yield 'opensearch dsn' => [
            'opensearch://user:pass@127.0.0.1:9200?tls=true',
            'opensearch://user:pass@127.0.0.1:9200?tls=true',
        ];

        yield 'https url' => [
            'https://user:pass@example.com:9200',
            'opensearch://user:pass@example.com:9200?tls=true',
        ];

        yield 'https url with path prefix' => [
            'https://user:pass@example.com/opensearch',
            'opensearch://user:pass@example.com/opensearch?tls=true',
        ];
    }
}
