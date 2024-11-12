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
 * @template-covariant T of mixed
 */
interface TaskInterface
{
    /**
     * In most cases for performance reasons it should be avoided to wait for a task to finish.
     * The search index normally correctly schedules syncs that we even don't need to wait example
     * that the index is created to index documents.
     * So the main usecase for return a task and wait for it are inside tests where index create and drops are tested.
     *
     * @return T
     */
    public function wait(): mixed;
}
