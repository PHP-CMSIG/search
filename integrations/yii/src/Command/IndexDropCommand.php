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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
final class IndexDropCommand extends Command
{
    public function __construct(private readonly EngineRegistry $engineRegistry)
    {
        parent::__construct('cmsig:seal:index-drop');
    }

    protected function configure(): void
    {
        $this->setDescription('Drop configured search indexes.');
        $this->addArgument('engine', InputArgument::OPTIONAL, 'The name of the engine to create the schema for.');
        $this->addArgument('index', InputArgument::OPTIONAL, 'The name of the index to create the schema for.');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Without force nothing will happen in this command.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        /** @var string|null $engineName */
        $engineName = $input->getArgument('engine');
        /** @var string|null $indexName */
        $indexName = $input->getArgument('index');
        $force = $input->getOption('force');

        if (!$force) {
            $ui->error('You need to use the --force option to drop the search indexes.');

            return Command::FAILURE;
        }

        foreach ($this->engineRegistry->getEngines() as $name => $engine) {
            if ($engineName && $engineName !== $name) {
                continue;
            }

            if ($indexName) {
                $ui->text('Dropping search index "' . $indexName . '" for "' . $name . '" ...');
                $task = $engine->dropIndex($indexName, ['return_slow_promise_result' => true]);
                $task->wait();

                continue;
            }

            $ui->text('Dropping search indexes of "' . $name . '" ...');
            $task = $engine->dropSchema(['return_slow_promise_result' => true]);
            $task->wait();
        }

        $ui->success('Search indexes dropped.');

        return Command::SUCCESS;
    }
}
