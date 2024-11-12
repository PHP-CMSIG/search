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

interface IndexerInterface
{
    /**
     * @param array<string, mixed> $document
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<array<string, mixed>> : null)
     */
    public function save(Index $index, array $document, array $options = []): TaskInterface|null;

    /**
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function delete(Index $index, string $identifier, array $options = []): TaskInterface|null;

    /**
     * @param iterable<array<string, mixed>> $saveDocuments
     * @param iterable<string> $deleteDocumentIdentifiers
     * @param array{return_slow_promise_result?: true} $options
     *
     * @return ($options is non-empty-array ? TaskInterface<void|null> : null)
     */
    public function bulk(
        Index $index,
        iterable $saveDocuments,
        iterable $deleteDocumentIdentifiers,
        int $bulkSize = 100,
        array $options = [],
    ): TaskInterface|null;
}
