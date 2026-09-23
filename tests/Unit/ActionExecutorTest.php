<?php

declare(strict_types=1);

use AgentNative\Laravel\Engine\ActionExecutor;
use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Schema\SchemaCompiler;
use AgentNative\Laravel\Tests\Fixtures\Priority;
use AgentNative\Laravel\Tests\Fixtures\TaskRepository;
use AgentNative\Laravel\Tests\Fixtures\TaskService;

beforeEach(function () {
    $this->registry = new ActionRegistry(new SchemaCompiler);
    $this->registry->register(TaskService::class);
    $this->executor = new ActionExecutor($this->registry, $this->app);
});

it('resolves the class through the container and invokes the method', function () {
    $result = $this->executor->call('rename_task', ['id' => 7, 'title' => 'Ship it']);

    expect($result->ok)->toBeTrue()
        ->and($result->output)->toBe('Task 7 renamed to Ship it');
});

it('injects constructor dependencies via the container', function () {
    $repository = new TaskRepository;
    $this->app->instance(TaskRepository::class, $repository);

    $result = $this->executor->call('createTask', ['title' => 'Write tests', 'tags' => ['qa']]);

    expect($result->ok)->toBeTrue()
        ->and($repository->stored)->toHaveCount(1)
        ->and($repository->stored[0]['title'])->toBe('Write tests');
});

it('coerces backed enum arguments from their scalar values', function () {
    $repository = new TaskRepository;
    $this->app->instance(TaskRepository::class, $repository);

    $result = $this->executor->call('createTask', ['title' => 'Triage', 'priority' => 'high']);

    expect($result->ok)->toBeTrue()
        ->and($repository->stored[0]['priority'])->toBe(Priority::High->value);
});

it('fails on unknown action', function () {
    $result = $this->executor->call('doesNotExist');

    expect($result->ok)->toBeFalse()
        ->and($result->code)->toBe('unknown_action')
        ->and($result->error)->toContain('doesNotExist');
});

it('fails with missing_params listing each missing parameter', function () {
    $result = $this->executor->call('rename_task', ['title' => 'x']);

    expect($result->ok)->toBeFalse()
        ->and($result->code)->toBe('missing_params')
        ->and($result->error)->toContain('id');
});

it('rejects unknown parameters (additionalProperties parity)', function () {
    $result = $this->executor->call('rename_task', ['id' => 1, 'title' => 'x', 'evil' => true]);

    expect($result->ok)->toBeFalse()
        ->and($result->code)->toBe('invalid_param')
        ->and($result->error)->toContain('evil');
});

it('rejects wrong scalar types with a readable message', function () {
    $result = $this->executor->call('rename_task', ['id' => 'not-an-int', 'title' => 'x']);

    expect($result->ok)->toBeFalse()
        ->and($result->code)->toBe('invalid_param')
        ->and($result->error)->toContain('$id')->toContain('int');
});

it('rejects invalid enum values', function () {
    $result = $this->executor->call('createTask', ['title' => 'x', 'priority' => 'urgent']);

    expect($result->ok)->toBeFalse()
        ->and($result->code)->toBe('invalid_param')
        ->and($result->error)->toContain(Priority::class);
});

it('applies default values for omitted optional params', function () {
    $repository = new TaskRepository;
    $this->app->instance(TaskRepository::class, $repository);

    $this->executor->call('createTask', ['title' => 'Defaults']);

    expect($repository->stored[0])
        ->toMatchArray([
            'priority' => 'medium',
            'estimateHours' => null,
            'tags' => [],
            'notify' => false,
            'weight' => 1.0,
        ]);
});

it('catches exceptions and reports them as execution_error', function () {
    $this->app->bind(TaskRepository::class, fn () => throw new RuntimeException('database down'));

    $result = $this->executor->call('createTask', ['title' => 'Boom']);

    expect($result->ok)->toBeFalse()
        ->and($result->code)->toBe('execution_error');
});

it('normalizes enum returns to scalars', function () {
    $result = $this->executor->call('close', ['id' => 3]);

    expect($result->ok)->toBeTrue()
        ->and($result->output)->toBe('Task 3 is now Closed');
});

it('exposes the tool list in OpenAI format', function () {
    $tools = $this->registry->tools();

    expect($tools)->toBeArray()->not->toBeEmpty()
        ->and($tools[0])->toHaveKeys(['type', 'function']);
});
