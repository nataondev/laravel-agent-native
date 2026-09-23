<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Mcp;

use AgentNative\Laravel\Engine\ActionExecutor;
use AgentNative\Laravel\Schema\ActionDefinition;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Adapts one registered #[AgentAction] into a Laravel MCP Tool.
 *
 * The registry stays the single source of truth: name, description, and the
 * input schema all come from the compiled ActionDefinition, and execution
 * runs through the shared ActionExecutor (identical validation + DI to the
 * HTTP endpoint and the Livewire bridge).
 *
 * Requires the optional `laravel/mcp` package — see docs/mcp.md.
 */
final class AgentNativeTool extends Tool
{
    public function __construct(
        private readonly ActionDefinition $definition,
        private readonly ActionExecutor $executor,
    ) {}

    public function name(): string
    {
        return $this->definition->name;
    }

    public function description(): string
    {
        return $this->definition->description;
    }

    /**
     * Laravel MCP builds the JSON-Schema object from the array returned here;
     * returning the already-compiled properties keeps the registry schema
     * (enums, nested items, additionalProperties: false) intact.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * Expose the compiled schema verbatim as the MCP inputSchema.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $parameters = $this->definition->toToolSchema()['function']['parameters'];

        return [
            'name' => $this->name(),
            'title' => $this->name(),
            'description' => $this->description(),
            'inputSchema' => [
                'type' => 'object',
                'properties' => $parameters['properties'] === [] || $parameters['properties'] instanceof \stdClass
                    ? (object) []
                    : $parameters['properties'],
                'required' => $parameters['required'],
                'additionalProperties' => false,
            ],
            'annotations' => (object) [],
        ];
    }

    public function handle(Request $request): Response
    {
        $result = $this->executor->call($this->definition->name, $request->toArray());

        if (! $result->ok) {
            return Response::error("[{$result->code}] {$result->error}");
        }

        return Response::json($result->output);
    }
}
