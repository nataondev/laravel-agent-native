<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Attributes;

use Attribute;

/**
 * Marks a public method as an agent-invokable action.
 *
 * The same method can be called from your UI (Livewire/HTTP) and is
 * automatically exposed to LLM agents as an OpenAI/MCP tool definition.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class AgentAction
{
    public function __construct(
        public string $description,
        public ?string $name = null,
    ) {}
}
