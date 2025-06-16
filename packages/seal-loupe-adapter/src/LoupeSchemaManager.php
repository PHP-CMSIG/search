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

use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\SyncTask;
use CmsIg\Seal\Task\TaskInterface;

final class LoupeSchemaManager implements SchemaManagerInterface
{
    public function __construct(
        private readonly LoupeHelper $loupeHelper,
    ) {
    }

    public function existIndex(Index $index): bool
    {
        return $this->loupeHelper->existIndex($index);
    }

    public function dropIndex(Index $index, array $options = []): TaskInterface|null
    {
        $this->loupeHelper->dropIndex($index);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function createIndex(Index $index, array $options = []): TaskInterface|null
    {
        $this->loupeHelper->createIndex($index);
        $this->loupeHelper->getLoupe($index);

        if (!($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }
}
