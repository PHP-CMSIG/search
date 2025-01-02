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

use CmsIg\Seal\Schema\Index;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;

/**
 * @experimental
 */
final class LoupeHelper
{
    public const SEPARATOR = '_';
    public const SOURCE_FIELD = 'l_source'; // fields are not allowed to begin with `_`

    /**
     * @var Loupe[]
     */
    private array $loupe = [];

    private readonly string $directory;

    public function __construct(
        private readonly LoupeFactory $loupeFactory,
        string $directory,
    ) {
        $this->directory = '' !== $directory ? (\rtrim($directory, '/') . '/') : '';
    }

    public function getLoupe(Index $index): Loupe
    {
        if (!isset($this->loupe[$index->name])) {
            $this->loupe[$index->name] = $this->createLoupe($index);
        }

        return $this->loupe[$index->name];
    }

    public function existIndex(Index $index): bool
    {
        $indexDirectory = $this->getIndexDirectory($index);

        return \file_exists($indexDirectory);
    }

    public function dropIndex(Index $index): void
    {
        if ($this->existIndex($index)) {
            $indexDirectory = $this->getIndexDirectory($index);

            // beside the .db and our own .loupe file there exists other files which we need to remove
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($indexDirectory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $fileInfo) {
                \assert($fileInfo instanceof \SplFileInfo);

                $filePath = $fileInfo->getPathname();
                if ($fileInfo->isFile()) {
                    \unlink($filePath);
                } elseif ($fileInfo->isDir()) {
                    \rmdir($filePath);
                }
            }

            \rmdir($indexDirectory);
        }
    }

    public function createIndex(Index $index): void
    {
        $configuration = $this->createAndDumpConfiguration($index);
        \file_put_contents($this->getConfigurationFile($index), \serialize($configuration));
        $this->loupe[$index->name] = $this->createLoupe($index, $configuration);
    }

    public function reset(): void
    {
        $this->loupe = [];
    }

    /**
     * @internal
     */
    public function formatField(string $field): string
    {
        return \str_replace('.', self::SEPARATOR, $field);
    }

    private function createLoupe(Index $index, Configuration|null $configuration = null): Loupe
    {
        if (!$configuration instanceof Configuration) {
            $configurationFile = $this->getConfigurationFile($index);

            if (!\file_exists($configurationFile)) {
                $configuration = $this->createAndDumpConfiguration($index);
            } else {
                /** @var string $configurationContent */
                $configurationContent = \file_get_contents($configurationFile);

                /** @var Configuration $configuration */
                $configuration = \unserialize($configurationContent);
            }
        }

        if ('' === $this->directory) {
            return $this->loupeFactory->createInMemory($configuration);
        }

        return $this->loupeFactory->create($this->getIndexDirectory($index), $configuration);
    }

    private function createAndDumpConfiguration(Index $index): Configuration
    {
        $indexDirectory = $this->getIndexDirectory($index);
        if (!\file_exists($indexDirectory)) {
            \mkdir($indexDirectory, recursive: true);
        }

        // dumping the configuration allows us to search and index without knowing the configuration
        // this way when a similar class like this would be part of loupe only the createIndex method
        // would require then to know the configuration
        $configuration = $this->createConfiguration($index);
        \file_put_contents($this->getConfigurationFile($index), \serialize($configuration));

        return $configuration;
    }

    private function createConfiguration(Index $index): Configuration
    {
        return Configuration::create()
            ->withPrimaryKey($index->getIdentifierField()->name)
            ->withSearchableAttributes(\array_map(fn (string $field) => $this->formatField($field), $index->searchableFields))
            ->withFilterableAttributes(\array_map(fn (string $field) => $this->formatField($field), $index->filterableFields))
            ->withSortableAttributes(\array_map(fn (string $field) => $this->formatField($field), $index->sortableFields));
    }

    private function getIndexDirectory(Index $index): string
    {
        return $this->directory . $index->name . '/';
    }

    private function getConfigurationFile(Index $index): string
    {
        return $this->getIndexDirectory($index) . 'config.loupe';
    }
}
