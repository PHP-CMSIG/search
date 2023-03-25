<?php

declare(strict_types=1);

namespace Schranz\Search\SEAL\Adapter\Elasticsearch\Tests;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

final class ClientHelper
{
    private static ?Client $client = null;

    public static function getClient(): Client
    {
        if (!self::$client instanceof \Elastic\Elasticsearch\Client) {
            self::$client = ClientBuilder::create()->setHosts([
                $_ENV['ELASTICSEARCH_HOST'] ?? '127.0.0.1:9200',
            ])->build();
        }

        return self::$client;
    }
}
