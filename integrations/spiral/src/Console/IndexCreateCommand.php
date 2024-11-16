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

namespace CmsIg\Seal\Integration\Spiral\Console;

use CmsIg\Seal\EngineRegistry;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Attribute\Option;
use Spiral\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * @experimental
 */
#[AsCommand(
    name: 'cmsig:seal:index-create',
    description: 'Create configured search indexes.',
)]
final class IndexCreateCommand extends Command
{
    #[Option(name: 'engine', mode: InputOption::VALUE_REQUIRED, description: 'The name of the engine')]
    private string|null $engineName = null;

    #[Option(name: 'index', mode: InputOption::VALUE_REQUIRED, description: 'The name of the index')]
    private string|null $indexName = null;

    public function __invoke(EngineRegistry $engineRegistry): int
    {
        foreach ($engineRegistry->getEngines() as $name => $engine) {
            if ($this->engineName && $this->engineName !== $name) {
                continue;
            }

            if ($this->indexName) {
                $this->line('Creating search index "' . $this->indexName . '" of "' . $name . '" ...');
                $task = $engine->createIndex($this->indexName, ['return_slow_promise_result' => true]);
                $task->wait();

                continue;
            }

            $this->line('Creating search indexes of "' . $name . '" ...');
            $task = $engine->createSchema(['return_slow_promise_result' => true]);
            $task->wait();
        }

        $this->info('Search indexes created.');

        return self::SUCCESS;
    }
}
