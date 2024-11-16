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

namespace CmsIg\Seal\Integration\Yii\Command;

use CmsIg\Seal\EngineRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
final class IndexCreateCommand extends Command
{
    public function __construct(private readonly EngineRegistry $engineRegistry)
    {
        parent::__construct('cmsig:seal:index-create');
    }

    protected function configure(): void
    {
        $this->setDescription('Create configured search indexes.');
        $this->addOption('engine', null, InputOption::VALUE_REQUIRED, 'The name of the engine to create the schema for.');
        $this->addOption('index', null, InputOption::VALUE_REQUIRED, 'The name of the index to create the schema for.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        /** @var string|null $engineName */
        $engineName = $input->getOption('engine');
        /** @var string|null $indexName */
        $indexName = $input->getOption('index');

        foreach ($this->engineRegistry->getEngines() as $name => $engine) {
            if ($engineName && $engineName !== $name) {
                continue;
            }

            if ($indexName) {
                $ui->text('Creating search index "' . $indexName . '" of "' . $name . '" ...');
                $task = $engine->createIndex($indexName, ['return_slow_promise_result' => true]);
                $task->wait();

                continue;
            }

            $ui->text('Creating search indexes of "' . $name . '" ...');
            $task = $engine->createSchema(['return_slow_promise_result' => true]);
            $task->wait();
        }

        $ui->success('Search indexes created.');

        return Command::SUCCESS;
    }
}
