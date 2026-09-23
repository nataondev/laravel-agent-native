<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Livewire;

use AgentNative\Laravel\Engine\ActionRegistry;

/**
 * Add to any Livewire component to make its #[AgentAction] methods
 * agent-invokable, and to expose component state as agent context.
 *
 * Usage:
 *
 *   class Cart extends Component
 *   {
 *       use InteractsWithAgent;
 *
 *       #[AgentAction(description: 'Add a product to the cart')]
 *       public function addItem(int $productId, int $quantity = 1): void { ... }
 *   }
 */
trait InteractsWithAgent
{
    /**
     * Livewire boot hook: register this component's actions at mount.
     */
    public function bootInteractsWithAgent(ActionRegistry $registry): void
    {
        $registry->register(static::class);
    }

    /**
     * State shared with the agent (mirrors agent-native "shared application
     * state"). Override to restrict or enrich what the agent can see.
     *
     * @return array<string, mixed>
     */
    public function agentContext(): array
    {
        return $this->all();
    }
}
