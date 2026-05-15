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

namespace CmsIg\Seal\Schema\Loader;

use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;

final class PhpFileLoader implements LoaderInterface
{
    private readonly IndexMerger $indexMerger;

    /**
     * @param string[] $directories
     */
    public function __construct(
        private readonly array $directories,
        private readonly string $indexNamePrefix = '',
    ) {
        $this->indexMerger = new IndexMerger();
    }

    public function load(): Schema
    {
        /** @var array<string, Index> $indexes */
        $indexes = [];

        foreach ($this->directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory),
                \RecursiveIteratorIterator::LEAVES_ONLY,
            );

            /** @var array<string, Index> $pathIndexes */
            $pathIndexes = [];
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo) {
                    continue;
                }

                if ('php' !== $file->getFileInfo()->getExtension()) {
                    continue;
                }

                $index = require $file->getRealPath();

                if (!$index instanceof Index) {
                    throw new \RuntimeException(\sprintf('File "%s" must return an instance of "%s".', $file->getRealPath(), Index::class));
                }

                $pathIndexes[$file->getRealPath()] = $index;
            }

            \ksort($pathIndexes); // make sure to import the files on all system in the same order

            foreach ($pathIndexes as $index) {
                $indexes = $this->indexMerger->mergeIndexes($indexes, $index, $this->indexNamePrefix);
            }
        }

        return new Schema($indexes);
    }
}
