<?php

declare(strict_types=1);

use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Http\Controllers\AgentToolsController;
use AgentNative\Laravel\Tests\Fixtures\DenyAllAuthorizer;
use AgentNative\Laravel\Tests\Fixtures\TaskRepository;
use AgentNative\Laravel\Tests\Fixtures\TaskService;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('agent-native.classes', [TaskService::class]);
    config()->set('agent-native.cache_path', null);
    $this->app->forgetInstance(ActionRegistry::class);

    Route::middleware('api')->group(function () {
        Route::get('/agent/tools', [AgentToolsController::class, 'index']);
        Route::post('/agent/tools', [AgentToolsController::class, 'store']);
    });
});

it('lists tool schemas', function () {
    $this->getJson('/agent/tools')
        ->assertOk()
        ->assertJsonPath('tools.0.type', 'function')
        ->assertJsonStructure(['tools' => [['function' => ['name', 'description', 'parameters']]]]);
});

it('executes a valid tool call', function () {
    $this->postJson('/agent/tools', [
        'name' => 'rename_task',
        'arguments' => ['id' => 5, 'title' => 'Refactor'],
    ])->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('output', 'Task 5 renamed to Refactor');
});

it('rejects invalid payloads with 422 from request validation', function () {
    $this->postJson('/agent/tools', ['arguments' => []])
        ->assertUnprocessable();
});

it('returns 422 with structured error for unknown actions', function () {
    $this->postJson('/agent/tools', ['name' => 'nuke_everything'])
        ->assertUnprocessable()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('code', 'unknown_action');
});

it('returns 422 for missing parameters', function () {
    $this->postJson('/agent/tools', [
        'name' => 'rename_task',
        'arguments' => ['title' => 'x'],
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'missing_params');
});

it('returns 403 when the endpoint is disabled', function () {
    config()->set('agent-native.http.enabled', false);

    $this->getJson('/agent/tools')->assertForbidden();
    $this->postJson('/agent/tools', ['name' => 'rename_task', 'arguments' => ['id' => 1, 'title' => 'x']])
        ->assertForbidden();
});

it('denies execution when the authorize hook returns false', function () {
    config()->set('agent-native.http.authorize', fn () => false);

    $this->postJson('/agent/tools', [
        'name' => 'rename_task',
        'arguments' => ['id' => 1, 'title' => 'x'],
    ])->assertForbidden();
});

it('allows execution when the authorize hook returns true', function () {
    config()->set('agent-native.http.authorize', fn () => true);

    $this->postJson('/agent/tools', [
        'name' => 'rename_task',
        'arguments' => ['id' => 1, 'title' => 'x'],
    ])->assertOk();
});

it('supports class@method authorizers resolved through the container', function () {
    config()->set('agent-native.http.authorize', DenyAllAuthorizer::class.'@authorize');

    $this->postJson('/agent/tools', [
        'name' => 'rename_task',
        'arguments' => ['id' => 1, 'title' => 'x'],
    ])->assertForbidden();
});

it('never exposes stack traces on execution errors', function () {
    $this->app->bind(TaskRepository::class, fn () => throw new RuntimeException('db exploded'));

    $response = $this->postJson('/agent/tools', [
        'name' => 'createTask',
        'arguments' => ['title' => 'Boom'],
    ])->assertUnprocessable();

    expect($response->getContent())
        ->not->toContain('TaskRepository.php')
        ->not->toContain('stacktrace')
        ->not->toContain('#0 ');
});
