<?php

declare(strict_types=1);

use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Mcp\AgentNativeServer;
use AgentNative\Laravel\Tests\Fixtures\TaskService;
use Laravel\Mcp\Request;

beforeEach(function () {
    config()->set('agent-native.classes', [TaskService::class]);
    config()->set('agent-native.cache_path', null);
    $this->app->forgetInstance(ActionRegistry::class);
});

it('builds an MCP tool for every registered action', function () {
    $server = $this->app->make(AgentNativeServer::class);

    $tools = $server->createContext()->tools();

    expect($tools)->toHaveCount(4);

    $names = $tools->map(fn ($tool) => $tool->name())->all();
    expect($names)->toContain('createTask', 'rename_task', 'close', 'deleteMany');
});

it('exposes the compiled schema as MCP inputSchema', function () {
    $server = $this->app->make(AgentNativeServer::class);

    $tool = collect($server->createContext()->tools())
        ->first(fn ($t) => $t->name() === 'rename_task');

    $array = $tool->toArray();

    expect($array['name'])->toBe('rename_task')
        ->and($array['description'])->toBe('Rename an existing task')
        ->and($array['inputSchema']['type'])->toBe('object')
        ->and($array['inputSchema']['properties']['id']['type'])->toBe('integer')
        ->and($array['inputSchema']['required'])->toBe(['id', 'title'])
        ->and($array['inputSchema']['additionalProperties'])->toBeFalse();
});

it('executes a tool call through the shared executor', function () {
    $server = $this->app->make(AgentNativeServer::class);
    $tool = collect($server->createContext()->tools())->first(fn ($t) => $t->name() === 'rename_task');

    $response = $tool->handle(new Request(['id' => 9, 'title' => 'Bridge']));

    expect((string) $response->content())->toContain('Task 9 renamed to Bridge');
});

it('maps validation failures to MCP error responses', function () {
    $server = $this->app->make(AgentNativeServer::class);
    $tool = collect($server->createContext()->tools())->first(fn ($t) => $t->name() === 'rename_task');

    $response = $tool->handle(new Request(['title' => 'x']));

    expect((string) $response->content())->toContain('missing_params');
});
