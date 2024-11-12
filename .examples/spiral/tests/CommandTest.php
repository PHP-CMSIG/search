<?php

declare(strict_types=1);

namespace Tests;

final class CommandTest extends TestCase
{
    public function testCreate(): void
    {
        $this->assertCommandRegistered('seal:index-create');
        $output = $this->runCommand('seal:index-create');

        $this->assertStringContainsString('Search indexes created.', $output);
    }

    public function testDrop(): void
    {
        $this->assertCommandRegistered('seal:index-drop');
        $output = $this->runCommand('seal:index-drop', ['--force' => true]);

        $this->assertStringContainsString('Search indexes dropped.', $output);
    }

    public function testReindex(): void
    {
        $this->assertCommandRegistered('seal:reindex');
        $output = $this->runCommand('seal:reindex', ['--drop' => true]);

        $this->assertStringContainsString('3/3', $output);
        $this->assertStringContainsString('Search indexes reindexed.', $output);
    }
}
