<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Quonain\SmartResponse\SmartResponseServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SmartResponseServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('smart-response', require dirname(__DIR__).'/config/smart-response.php');
        $app['config']->set('view.paths', [dirname(__DIR__).'/tests/fixtures/views']);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
