<?php

declare(strict_types=1);

namespace Tests;

final class CommandTest extends TestCase
{
    public function testCreate(): void
    {
        $this->assertCommandRegistered('cmsig:seal:index-create');
        $output = $this->runCommand('cmsig:seal:index-create');

        $this->assertStringContainsString('Search indexes created.', $output);
    }

    public function testReindex(): void
    {
        $this->assertCommandRegistered('cmsig:seal:reindex');
        $output = $this->runCommand('cmsig:seal:reindex', ['--drop' => true]);

        $this->assertStringContainsString('3/3', $output);
        $this->assertStringContainsString('Search indexes reindexed.', $output);
    }

    public function testDrop(): void
    {
        $this->assertCommandRegistered('cmsig:seal:index-drop');
        $output = $this->runCommand('cmsig:seal:index-drop', ['--force' => true]);

        $this->assertStringContainsString('Search indexes dropped.', $output);
    }
}
