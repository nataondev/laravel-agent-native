<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Mcp;

use AgentNative\Laravel\Engine\ActionExecutor;
use AgentNative\Laravel\Engine\ActionRegistry;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

/**
 * Dynamic MCP server exposing every registered #[AgentAction] as a tool.
 *
 * ServerContext accepts Tool instances directly, so we materialize one
 * AgentNativeTool per registry definition at construction — no generated
 * classes, no eval, registry stays the single source of truth.
 *
 * Register it in routes/ai.php:
 *
 *   Mcp::web('/mcp/agent', \AgentNative\Laravel\Mcp\AgentNativeServer::class)
 *       ->middleware(['auth:sanctum', 'throttle:60,1']);
 *
 * Requires the optional `laravel/mcp` package — see docs/mcp.md.
 */
#[Name('Laravel Agent Native')]
#[Version('1.0.0')]
#[Instructions('Exposes the application\'s #[AgentAction] methods as MCP tools. Each tool maps 1:1 to a shared action used by the UI and other agent surfaces.')]
class AgentNativeServer extends Server
{
    /**
     * @var array<int, Tool>
     */
    protected array $tools = [];

    public function __construct(ActionRegistry $registry, ActionExecutor $executor)
    {
        foreach ($registry->all() as $definition) {
            $this->tools[] = new AgentNativeTool($definition, $executor);
        }
    }
}
