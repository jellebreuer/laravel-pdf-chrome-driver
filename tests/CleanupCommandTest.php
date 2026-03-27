<?php

use Breuer\MakePDF\Client;
use Symfony\Component\Process\Process;

use function Pest\Laravel\artisan;

it('outputs no orphaned processes when there are none', function () {
    artisan('make-pdf:cleanup')
        ->expectsOutput('No orphaned processes found.')
        ->assertSuccessful();
})->skipOnWindows();

it('kills orphaned chromedriver processes', function () {
    $process = new Process([Client::chromeDriverBinary(), '--port=9599']);
    $process->start();

    expect($process->isRunning())->toBeTrue();

    artisan('make-pdf:cleanup', ['--older-than' => 0])
        ->expectsOutputToContain('Killing chromedriver')
        ->assertSuccessful();

    // Give it a moment to receive SIGTERM
    usleep(100000);

    expect($process->isRunning())->toBeFalse();
})->skipOnWindows();
