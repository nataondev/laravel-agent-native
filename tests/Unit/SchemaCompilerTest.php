<?php

declare(strict_types=1);

use AgentNative\Laravel\Schema\ActionDefinition;
use AgentNative\Laravel\Schema\SchemaCompiler;
use AgentNative\Laravel\Tests\Fixtures\GreeterService;
use AgentNative\Laravel\Tests\Fixtures\Priority;
use AgentNative\Laravel\Tests\Fixtures\TaskService;

beforeEach(function () {
    $this->compiler = new SchemaCompiler;
});

it('compiles only attributed methods', function () {
    $definitions = $this->compiler->compile(TaskService::class);

    expect(array_column($definitions, 'method'))
        ->toContain('createTask', 'rename', 'close', 'deleteMany')
        ->not->toContain('notAnAction');
});

it('maps scalar types to JSON schema', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'createTask');

    expect($definition->properties)
        ->toMatchArray([
            'title' => ['type' => 'string', 'description' => 'The task title'],
            'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high'], 'description' => 'Priority level for triage'],
            'estimateHours' => ['type' => 'integer'],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Labels to attach'],
            'notify' => ['type' => 'boolean'],
            'weight' => ['type' => 'number'],
        ]);
});

it('infers required params from nullability and defaults', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'createTask');

    expect($definition->required)->toBe(['title']);
});

it('honours AgentParam required override', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'rename');

    expect($definition->required)->toBe(['id', 'title']);
});

it('supports custom action names', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'rename');

    expect($definition->name)->toBe('rename_task');
});

it('maps int-backed enums to integer enum schema', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'close');

    expect($definition->properties['status'])
        ->toBe(['type' => 'string', 'enum' => ['Open', 'Closed']]);
});

it('maps int[] docblock to array of integers', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'deleteMany');

    expect($definition->properties['ids'])
        ->toBe(['type' => 'array', 'items' => ['type' => 'integer']]);
});

it('exposes every public method on AgentExpose classes', function () {
    $definitions = $this->compiler->compile(GreeterService::class);

    expect(array_column($definitions, 'method'))
        ->toContain('greet', 'farewell')
        ->not->toContain('secret');

    $greet = collect($definitions)->firstWhere('method', 'greet');
    expect($greet->description)->toBe('Greet a person by name.');
});

it('produces valid OpenAI tool schema', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'createTask');

    $schema = $definition->toToolSchema();

    expect($schema['type'])->toBe('function')
        ->and($schema['function']['name'])->toBe('createTask')
        ->and($schema['function']['description'])->toBe('Create a task in the backlog')
        ->and($schema['function']['parameters']['type'])->toBe('object')
        ->and($schema['function']['parameters']['required'])->toBe(['title'])
        ->and($schema['function']['parameters']['additionalProperties'])->toBeFalse();

    expect($schema['function']['parameters']['properties']['title']['type'])->toBe('string');
});

it('serializes zero-param tools with an empty JSON object', function () {
    $definition = new ActionDefinition(
        name: 'ping',
        description: 'Ping',
        class: 'Foo',
        method: 'ping',
        properties: [],
        required: [],
    );

    $json = json_encode($definition);

    expect($json)->toContain('"properties":{}');
});

it('round-trips through array for caching', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'createTask');

    $restored = ActionDefinition::fromArray($definition->toArray());

    expect($restored)->toEqual($definition);
});

it('compiles backed string enum values', function () {
    $definition = collect($this->compiler->compile(TaskService::class))
        ->firstWhere('method', 'createTask');

    expect($definition->properties['priority']['enum'])
        ->toBe(array_map(fn (Priority $p) => $p->value, Priority::cases()));
});
