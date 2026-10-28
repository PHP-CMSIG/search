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
use Illuminate\Console\Command;

/**
 * @experimental
 */
final class IndexUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmsig:seal:index-update {--engine= : The name of the engine} {--index= : The name of the index} {--force : Force to update the indexes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update configured search indexes.';

    /**
     * Execute the console command.
     */
    public function handle(EngineRegistry $engineRegistry): int
    {
        /** @var string|null $engineName */
        $engineName = $this->option('engine');
        /** @var string|null $indexName */
        $indexName = $this->option('index');
        /** @var bool $force */
        $force = $this->option('force') ?: false;

        if (!$force) {
            $this->error('You need to use the --force option to update the search indexes.');

            return 1;
        }

        foreach ($engineRegistry->getEngines() as $name => $engine) {
            if ($engineName && $engineName !== $name) {
                continue;
            }

            if ($indexName) {
                $this->line('Updating search index "' . $indexName . '" of "' . $name . '" ...');
                $task = $engine->updateIndex($indexName, ['return_slow_promise_result' => true]);
                $task->wait();

                continue;
            }

            $this->line('Updating search indexes of "' . $name . '" ...');
            $task = $engine->updateSchema(['return_slow_promise_result' => true]);
            $task->wait();
        }

        $this->info('Search indexes updated.');

        return 0;
    }
}
