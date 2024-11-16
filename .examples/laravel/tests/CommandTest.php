<?php

declare(strict_types=1);

it('test create', function () {
    $this->artisan('cmsig:seal:index-create')
        ->assertExitCode(0);
});

it('test reindex', function () {
    $this->artisan('cmsig:seal:reindex --drop')
        ->assertExitCode(0)
        ->expectsOutputToContain('3/3');
});

it('test drop', function () {
    $this->artisan('cmsig:seal:index-drop --force')
        ->assertExitCode(0);
});
