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

namespace CmsIg\Seal\Adapter;

use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Task\TaskInterface;

interface SchemaManagerInterface
{
    public function existIndex(Index $index): bool;

    /**
     * @template T of bool
     *
     * @param array{return_slow_promise_result?: T} $options
     *
     * @return (T is true ? TaskInterface<void|null> : null)
     */
    public function dropIndex(Index $index, array $options = []): TaskInterface|null;

    /**
     * @template T of bool
     *
     * @param array{return_slow_promise_result?: T} $options
     *
     * @return (T is true ? TaskInterface<void|null> : null)
     */
    public function createIndex(Index $index, array $options = []): TaskInterface|null;

    /**
     * Updates an existing index to the given definition. Currently only adding new fields is supported,
     * changing or removing existing fields is out of scope and the behaviour is undefined.
     *
     * @template T of bool
     *
     * @param array{return_slow_promise_result?: T} $options
     *
     * @return (T is true ? TaskInterface<void|null> : null)
     */
    public function updateIndex(Index $index, array $options = []): TaskInterface|null;
}
