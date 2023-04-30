<?php

declare(strict_types=1);

/*
 * This file is part of the Schranz Search package.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Schranz\Search\Integration\Symfony\Command;

use Schranz\Search\SEAL\EngineRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
#[AsCommand(name: 'schranz:search:index-drop', description: 'Drop configured search indexes.')]
final class IndexDropCommand extends Command
{
    public function __construct(private readonly EngineRegistry $engineRegistry)
    {
        parent::__construct();
    }

    protected function configure()
    {
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
                $engine->dropIndex($indexName);

                continue;
            }

            $ui->text('Dropping search indexes of "' . $name . '" ...');
            $engine->dropSchema();
        }

        $ui->success('Search indexes dropped.');

        return Command::SUCCESS;
    }
}
