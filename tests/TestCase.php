<?php

namespace Breuer\ChromeDriver\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-pdf.driver', 'chrome');
    }
}
