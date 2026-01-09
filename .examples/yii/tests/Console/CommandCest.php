<?php

declare(strict_types=1);

namespace App\Tests\Console;

use App\Tests\Support\ConsoleTester;

final class CommandCest
{
    public function testCreate(ConsoleTester $I): void
    {
        $command = \dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' cmsig:seal:index-create');
        $I->seeInShellOutput('Search indexes created.');
    }

    public function testReindex(ConsoleTester $I): void
    {
        $command = \dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' cmsig:seal:reindex --drop');
        $I->seeInShellOutput('Search indexes reindexed.');
    }

    public function testDrop(ConsoleTester $I): void
    {
        $command = \dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' cmsig:seal:index-drop --force');
        $I->seeInShellOutput('Search indexes dropped.');
    }
}
