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

namespace CmsIg\Seal\Adapter\MongoDB;

use MongoDB\Client;
use MongoDB\Database;

final class ClientWrapper
{
    public function __construct(
        private readonly Client $client,
        private readonly string $databaseName,
    ) {
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getDatabase(): Database
    {
        return $this->client->getDatabase($this->databaseName);
    }
}
