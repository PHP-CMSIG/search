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

namespace CmsIg\Seal\Testing;

use CmsIg\Seal\Task\MultiTask;
use CmsIg\Seal\Task\TaskInterface;

/**
 * @interal
 */
final class TaskHelper
{
    /**
     * @var TaskInterface<mixed>[]
     */
    public array $tasks = [];

    public function waitForAll(): void
    {
        (new MultiTask($this->tasks))->wait();

        $this->tasks = [];
    }
}
