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

namespace CmsIg\Seal\Task;

/**
 * A Task object which waits for multiple tasks.
 *
 * @template-implements TaskInterface<null>
 */
final class MultiTask implements TaskInterface
{
    /**
     * @param TaskInterface<mixed>[] $tasks
     */
    public function __construct(
        private readonly array $tasks,
    ) {
    }

    /**
     * @return null
     */
    public function wait(): mixed
    {
        foreach ($this->tasks as $task) {
            $task->wait();
        }

        return null;
    }
}
