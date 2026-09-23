<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Tests;

use AgentNative\Laravel\AgentNativeServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            AgentNativeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('agent-native.cache_path', null);
        $app['view']->addNamespace('agent-native-test', __DIR__.'/Fixtures/views');
    }
}
