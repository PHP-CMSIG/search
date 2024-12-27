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

namespace CmsIg\Seal\Integration\Laravel\Console;

use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Illuminate\Console\Command;

/**
 * @experimental
 */
final class ReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmsig:seal:reindex {--engine= : The name of the engine} {--index= : The name of the index} {--drop : Drop the index before reindexing} {--bulk-size= : The bulk size for reindexing, defaults to 100.} {--datetime-boundary= : Do a partial update and limit to only documents that have been changed since a given datetime object.} {--identifiers= : Do a partial update and limit to only a comma-separated list of identifiers.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex configured search indexes.';

    /**
     * @param iterable<ReindexProviderInterface> $reindexProviders
     */
    public function __construct(
        private readonly iterable $reindexProviders, // TODO move to handle method: https://discord.com/channels/297040613688475649/1105593000664498336
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(
        EngineRegistry $engineRegistry,
    ): int {
        /** @var string|null $engineName */
        $engineName = $this->option('engine');
        /** @var string|null $indexName */
        $indexName = $this->option('index');
        /** @var bool $drop */
        $drop = $this->option('drop');
        /** @var int $bulkSize */
        $bulkSize = ((int) $this->option('bulk-size')) ?: 100;
        /** @var \DateTimeImmutable|null $dateTimeBoundary */
        $dateTimeBoundary = $this->option('datetime-boundary') ? new \DateTimeImmutable((string) $this->option('datetime-boundary')) : null; // @phpstan-ignore-line
        /** @var array<string> $identifiers */
        $identifiers = \array_filter(\explode(',', (string) $this->option('identifiers'))); // @phpstan-ignore-line

        $reindexConfig = ReindexConfig::create()
            ->withIndex($indexName)
            ->withBulkSize($bulkSize)
            ->withDropIndex($drop)
            ->withDateTimeBoundary($dateTimeBoundary)
            ->withIdentifiers($identifiers);

        foreach ($engineRegistry->getEngines() as $name => $engine) {
            if ($engineName && $engineName !== $name) {
                continue;
            }

            $this->line('Engine: ' . $name);

            $progressBar = $this->output->createProgressBar();

            $engine->reindex(
                $this->reindexProviders,
                $reindexConfig,
                function (string $index, int $count, int|null $total) use ($progressBar) {
                    if (null !== $total) {
                        $progressBar->setMaxSteps($total);
                    }

                    $progressBar->setMessage($index);
                    $progressBar->setProgress($count);
                },
            );

            $progressBar->finish();
            $this->line('');
            $this->line('');
        }

        $this->info('Search indexes reindexed.');

        return 0;
    }
}
