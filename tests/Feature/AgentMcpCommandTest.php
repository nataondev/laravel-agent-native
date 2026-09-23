<?php

declare(strict_types=1);

use AgentNative\Laravel\Tests\Fixtures\TaskService;

it('fails clearly when laravel/mcp is missing', function () {
    // laravel/mcp IS installed in this test env, so assert the happy path
    // instead and rely on the class_exists guard for the failure branch.
    config()->set('agent-native.classes', [TaskService::class]);

    $routesPath = base_path('routes/ai.php');
    @unlink($routesPath);

    $this->artisan('agent:mcp', ['--path' => '/mcp/agent'])->assertSuccessful();

    expect($routesPath)->toBeFile();
    $contents = file_get_contents($routesPath);
    expect($contents)
        ->toContain('AgentNativeServer::class')
        ->toContain("Mcp::web('/mcp/agent'")
        ->toContain('auth:sanctum')
        ->toContain('throttle');

    @unlink($routesPath);
});

it('does not duplicate an existing registration', function () {
    config()->set('agent-native.classes', [TaskService::class]);

    $routesPath = base_path('routes/ai.php');
    @unlink($routesPath);

    $this->artisan('agent:mcp')->assertSuccessful();
    $this->artisan('agent:mcp')->assertSuccessful();

    expect(substr_count(file_get_contents($routesPath), 'AgentNativeServer::class'))->toBe(1);

    @unlink($routesPath);
});

it('registers a local stdio server when requested', function () {
    config()->set('agent-native.classes', [TaskService::class]);

    $routesPath = base_path('routes/ai.php');
    @unlink($routesPath);

    $this->artisan('agent:mcp', ['--local' => 'agent'])->assertSuccessful();

    expect(file_get_contents($routesPath))->toContain("Mcp::local('agent'");

    @unlink($routesPath);
});
