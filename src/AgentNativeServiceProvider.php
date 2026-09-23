<?php

declare(strict_types=1);

namespace AgentNative\Laravel;

use AgentNative\Laravel\Engine\ActionExecutor;
use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Livewire\AgentBridge;
use AgentNative\Laravel\Schema\SchemaCompiler;
use Illuminate\Support\ServiceProvider;

final class AgentNativeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agent-native.php', 'agent-native');

        $this->app->singleton(SchemaCompiler::class);

        $this->app->singleton(ActionRegistry::class, function ($app) {
            $cachePath = $app['config']->get('agent-native.cache_path');

            $registry = new ActionRegistry(
                $app->make(SchemaCompiler::class),
                is_string($cachePath) ? $cachePath : null,
            );

            $registry->registerMany($app['config']->get('agent-native.classes', []));

            return $registry;
        });

        $this->app->singleton(ActionExecutor::class, fn ($app) => new ActionExecutor(
            $app->make(ActionRegistry::class),
            $app,
        ));

        $this->app->singleton(AgentBridge::class, fn ($app) => new AgentBridge(
            $app->make(ActionExecutor::class),
            $app['events'],
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/agent-native.php' => config_path('agent-native.php'),
            ], 'agent-native-config');

            $this->commands([
                Commands\AgentInspectCommand::class,
                Commands\AgentCacheCommand::class,
                Commands\AgentClearCommand::class,
                Commands\AgentMcpInstallCommand::class,
                Commands\AgentDiscoverCommand::class,
            ]);
        }
    }
}
