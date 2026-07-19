<?php

namespace Forestry\LogCabin\Laravel\Tests;

use Forestry\LogCabin\Laravel\LogCabinServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LogCabinServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('logcabin.endpoint', 'https://logcabin.test');
        $app['config']->set('logcabin.token', 'test-token');
    }
}
