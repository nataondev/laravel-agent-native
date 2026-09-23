<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Livewire;

use AgentNative\Laravel\Engine\ActionResult;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever the agent performs an action through the AgentBridge.
 * Listen from Livewire components or queue listeners to keep UIs in sync.
 */
final class ActionPerformed
{
    use Dispatchable;

    public function __construct(
        public readonly ActionResult $result,
    ) {}
}
