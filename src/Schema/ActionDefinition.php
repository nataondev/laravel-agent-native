<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Schema;

/**
 * Immutable, arrayable definition of a single agent action (tool).
 *
 * This is the compiled artifact: it can be cached to disk and rehydrated
 * without reflection. `toToolSchema()` produces the OpenAI tool-calling
 * format, which is also the MCP `tools/list` shape modulo naming.
 */
final readonly class ActionDefinition implements \JsonSerializable
{
    /**
     * @param  array<string, array<string, mixed>>  $properties  JSON-Schema properties keyed by param name
     * @param  list<string>  $required
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $class,
        public string $method,
        public array $properties,
        public array $required,
    ) {}

    /**
     * OpenAI tool-calling schema.
     *
     * @return array{type: string, function: array{name: string, description: string, parameters: array<string, mixed>}}
     */
    public function toToolSchema(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->properties === [] ? new \stdClass : $this->properties,
                    'required' => $this->required,
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toToolSchema();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'class' => $this->class,
            'method' => $this->method,
            'properties' => $this->properties,
            'required' => $this->required,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'],
            class: $data['class'],
            method: $data['method'],
            properties: $data['properties'],
            required: $data['required'],
        );
    }
}
