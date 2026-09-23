<?php

declare(strict_types=1);

use AgentNative\Laravel\Engine\ActionExecutor;
use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Livewire\ActionPerformed;
use AgentNative\Laravel\Livewire\AgentBridge;
use AgentNative\Laravel\Tests\Fixtures\Cart;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

it('registers component actions on mount via the trait', function () {
    $registry = $this->app->make(ActionRegistry::class);

    Livewire::test(Cart::class);

    expect($registry->find('addItem'))->not->toBeNull()
        ->and($registry->find('clear'))->not->toBeNull();
});

it('executes an agent action against the component and mutates state', function () {
    $component = Livewire::test(Cart::class);

    $result = $this->app->make(ActionExecutor::class)
        ->call('addItem', ['productId' => 42, 'quantity' => 2]);

    expect($result->ok)->toBeTrue();

    // The same action remains callable by the UI with identical semantics.
    $component->call('addItem', 42, 2);

    $component->assertSet('lastAdded', 42);
    expect($component->get('items'))->toHaveCount(1);
});

it('dispatches ActionPerformed when the bridge executes', function () {
    Livewire::test(Cart::class);

    Event::fake([ActionPerformed::class]);

    $result = $this->app->make(AgentBridge::class)->run('clear');

    expect($result->ok)->toBeTrue();

    Event::assertDispatched(ActionPerformed::class, fn ($event) => $event->result->ok && $event->result->action === 'clear');
});

it('exposes component state as agent context', function () {
    $component = Livewire::test(Cart::class)
        ->call('addItem', 5, 1);

    $context = $component->instance()->agentContext();

    expect($context)->toHaveKey('items')
        ->and($context['lastAdded'])->toBe(5);
});

it('agent tool schema for a component matches UI call semantics', function () {
    Livewire::test(Cart::class);

    $tool = collect($this->app->make(ActionRegistry::class)->tools())
        ->firstWhere('function.name', 'addItem');

    expect($tool['function']['parameters']['properties'])
        ->toMatchArray([
            'productId' => ['type' => 'integer'],
            'quantity' => ['type' => 'integer'],
        ])
        ->and($tool['function']['parameters']['required'])->toBe(['productId']);
});
