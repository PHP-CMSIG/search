#!/usr/bin/env php
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

// https://github.com/php-cmsig/search/issues/82

/** @internal */
require_once \dirname(__DIR__) . '/vendor/autoload.php';

if (!isset($_ENV['ALGOLIA_DSN'])) {
    if (!\file_exists(\dirname(__DIR__) . '/phpunit.xml')) {
        throw new \Exception('phpunit.xml not found');
    }

    $data = \file_get_contents(\dirname(__DIR__) . '/phpunit.xml');

    $xml = \simplexml_load_string($data);

    $algoliaDsn = $xml->xpath('//env[@name="ALGOLIA_DSN"]')[0]['value']->__toString();
}

$_ENV['ALGOLIA_DSN'] = $algoliaDsn;

$return = 0;

$client = \CmsIg\Seal\Adapter\Algolia\Tests\ClientHelper::getClient();
$retryIndexes = $client->listIndices()['items'];
$retryCounter = 0;

while (\count($retryIndexes) > 0) {
    $client = \CmsIg\Seal\Adapter\Algolia\Tests\ClientHelper::getClient();
    $currentIndexes = $retryIndexes;
    $retryIndexes = [];
    foreach ($currentIndexes as $key => $value) {
        echo 'Delete ... ' . $value['name'] . \PHP_EOL;

        try {
            $client->deleteIndex($value['name']);
        } catch (\Exception) {
            $retryIndexes[$key] = $value;
            echo 'Retry later ... ' . $value['name'] . \PHP_EOL;
        }
    }

    ++$retryCounter;
    if ($retryCounter >= 10) {
        break;
    }
}

if (\count($retryIndexes) > 0) {
    $return = 1;
}

exit($return);
