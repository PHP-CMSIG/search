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
 * An easier to use Task object for synchronous tasks.
 *
 * @template-covariant T of mixed
 *
 * @template-implements TaskInterface<T>
 */
final class SyncTask implements TaskInterface
{
    /**
     * @param T $result
     */
    public function __construct(
        private readonly mixed $result,
    ) {
    }

    public function wait(): mixed
    {
        return $this->result;
    }
}
