<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Livewire;

use AgentNative\Laravel\Engine\ActionExecutor;
use AgentNative\Laravel\Engine\ActionResult;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Entry point for agent-driven execution with Livewire reactivity.
 *
 * Execution runs through the shared ActionExecutor (identical semantics to a
 * UI call), then an ActionPerformed event is dispatched. Livewire components
 * listen for it to re-render or refresh their data:
 *
 *   protected $listeners = ['agent-action-performed' => '$refresh'];
 *
 * Livewire 3 events are component-scoped; a Laravel event is the correct
 * server-side broadcast primitive for a global "the agent acted" signal.
 */
final class AgentBridge
{
    public const EVENT = 'agent-action-performed';

    public function __construct(
        private readonly ActionExecutor $executor,
        private readonly Dispatcher $events,
    ) {}

    /**
     * Execute an action and notify the application (and any listening
     * Livewire components) that the agent performed work.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function run(string $action, array $arguments = []): ActionResult
    {
        $result = $this->executor->call($action, $arguments);

        $this->events->dispatch(self::EVENT, [$result->jsonSerialize()]);
        $this->events->dispatch(new ActionPerformed($result));

        return $result;
    }
}
