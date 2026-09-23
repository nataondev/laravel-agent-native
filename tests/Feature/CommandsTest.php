<?php

declare(strict_types=1);

use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Schema\SchemaCompiler;
use AgentNative\Laravel\Tests\Fixtures\TaskService;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config()->set('agent-native.classes', [TaskService::class]);
    config()->set('agent-native.cache_path', null);

    // Re-bind the registry so config changes take effect per test.
    $this->app->forgetInstance(ActionRegistry::class);
    $this->app->make(ActionRegistry::class);
});

it('lists registered tools with their handlers and parameters', function () {
    Artisan::call('agent:inspect');

    $output = Artisan::output();

    expect($output)
        ->toContain('createTask')
        ->toContain('rename_task')
        ->toContain('Create a task in the backlog')
        ->toContain('title: string');
});

it('dumps raw tool schemas as JSON', function () {
    Artisan::call('agent:inspect', ['--json' => true]);

    $output = Artisan::output();

    expect($output)
        ->toContain('"name": "createTask"')
        ->toContain('additionalProperties');

    expect(json_decode($output, true, flags: JSON_THROW_ON_ERROR))
        ->toBeArray()->not->toBeEmpty();
});

it('warns when nothing is registered', function () {
    config()->set('agent-native.classes', []);
    $this->app->forgetInstance(ActionRegistry::class);

    $this->artisan('agent:inspect')
        ->expectsOutputToContain('No agent actions registered')
        ->assertSuccessful();
});

it('caches compiled schemas to disk', function () {
    $path = sys_get_temp_dir().'/agent-native-test-'.uniqid().'.php';
    config()->set('agent-native.cache_path', $path);
    $this->app->forgetInstance(ActionRegistry::class);

    $this->artisan('agent:cache')->assertSuccessful();

    expect($path)->toBeFile();

    $cached = require $path;
    expect($cached)->toBeArray()->not->toBeEmpty()
        ->and($cached[0])->toHaveKeys(['name', 'class', 'method', 'properties', 'required']);

    unlink($path);
});

it('loads definitions from cache without reflection', function () {
    $path = sys_get_temp_dir().'/agent-native-test-'.uniqid().'.php';
    config()->set('agent-native.cache_path', $path);
    $this->app->forgetInstance(ActionRegistry::class);
    $this->artisan('agent:cache');

    // A fresh registry pointed at the cache with NO classes registered
    // must still serve the cached definitions.
    $registry = new ActionRegistry(new SchemaCompiler, $path);

    expect($registry->find('rename_task'))->not->toBeNull()
        ->and($registry->tools())->not->toBeEmpty();

    unlink($path);
});

it('clears the schema cache', function () {
    $path = sys_get_temp_dir().'/agent-native-test-'.uniqid().'.php';
    file_put_contents($path, '<?php return [];');
    config()->set('agent-native.cache_path', $path);

    $this->artisan('agent:clear')->assertSuccessful();

    expect($path)->not->toBeFile();
});
