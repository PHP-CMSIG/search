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
     * Updates an existing index to the given definition, keeping its documents. Only adding new fields is supported:
     * removed fields are not removed and changed fields (settings or type) are not guaranteed to be applied,
     * some adapters ignore them while others apply them. Such changes require a reindex of the documents.
     *
     * @template T of bool
     *
     * @param array{return_slow_promise_result?: T} $options
     *
     * @return (T is true ? TaskInterface<void|null> : null)
     */
    public function updateIndex(Index $index, array $options = []): TaskInterface|null;
}
