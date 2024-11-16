<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CommandTest extends KernelTestCase
{
    public function testCreate(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('cmsig:seal:index-create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testReindex(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('cmsig:seal:reindex');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--drop' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('3/3', $commandTester->getDisplay());
    }

    public function testDrop(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('cmsig:seal:index-drop');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--force' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();
    }
}
