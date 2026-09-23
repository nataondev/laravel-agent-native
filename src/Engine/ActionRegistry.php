<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Engine;

use AgentNative\Laravel\Schema\ActionDefinition;
use AgentNative\Laravel\Schema\SchemaCompiler;

/**
 * Registry of all agent-exposed actions. Single source of truth consumed by
 * the tool-schema endpoint, the executor, and the artisan commands.
 *
 * When a cache file exists (php artisan agent:cache) definitions are loaded
 * from disk and reflection is skipped entirely.
 */
final class ActionRegistry
{
    /** @var array<string, ActionDefinition>|null */
    private ?array $definitions = null;

    /** @var list<class-string> */
    private array $classes = [];

    public function __construct(
        private readonly SchemaCompiler $compiler,
        private readonly ?string $cachePath = null,
    ) {}

    /** @param class-string $class */
    public function register(string $class): self
    {
        $this->classes[] = $class;
        $this->definitions = null;

        return $this;
    }

    /** @param iterable<class-string> $classes */
    public function registerMany(iterable $classes): self
    {
        foreach ($classes as $class) {
            $this->register($class);
        }

        return $this;
    }

    public function find(string $name): ?ActionDefinition
    {
        return $this->all()[$name] ?? null;
    }

    /** @return array<string, ActionDefinition> */
    public function all(): array
    {
        return $this->definitions ??= $this->load();
    }

    /** @return list<array<string, mixed>> OpenAI tool schemas */
    public function tools(): array
    {
        return array_values(array_map(
            static fn (ActionDefinition $definition) => $definition->toToolSchema(),
            $this->all(),
        ));
    }

    /** @return list<class-string> */
    public function classes(): array
    {
        return array_values(array_unique($this->classes));
    }

    public function forgetCache(): void
    {
        $this->definitions = null;
    }

    /** @return array<string, ActionDefinition> */
    private function load(): array
    {
        if ($this->cachePath !== null && is_file($this->cachePath)) {
            /** @var list<array<string, mixed>> $cached */
            $cached = require $this->cachePath;

            $definitions = [];
            foreach ($cached as $data) {
                $definition = ActionDefinition::fromArray($data);
                $definitions[$definition->name] = $definition;
            }

            return $definitions;
        }

        return $this->compileAll();
    }

    /** @return array<string, ActionDefinition> */
    public function compileAll(): array
    {
        $definitions = [];

        foreach ($this->classes() as $class) {
            foreach ($this->compiler->compile($class) as $definition) {
                $definitions[$definition->name] = $definition;
            }
        }

        return $definitions;
    }
}
