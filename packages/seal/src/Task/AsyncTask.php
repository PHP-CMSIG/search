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
 * A Task object for asynchronous tasks.
 *
 * As example Algolia returns us just a task id, which we can use to wait for the task to finish.
 *
 * @template-covariant T of mixed
 *
 * @template-implements TaskInterface<T>
 */
final class AsyncTask implements TaskInterface
{
    /**
     * @param \Closure(): T $callback
     */
    public function __construct(
        private readonly \Closure $callback,
    ) {
        // TODO check if async library (e.g. react-php) should call callback method already here
        //      for Agolia this is currently not required or possible as they use a blocking usleep
        //      see https://github.com/algolia/algoliasearch-client-php/issues/712
        //      but maybe for other adapters make sense to async resolve it here
    }

    public function wait(): mixed
    {
        return ($this->callback)();
    }
}
