<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Tests\Fixtures;

use AgentNative\Laravel\Attributes\AgentExpose;

/**
 * Greeter service exposed wholesale to the agent.
 */
#[AgentExpose(description: 'Greeting utilities')]
class GreeterService
{
    /** Greet a person by name. */
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }

    public function farewell(string $name = 'friend'): string
    {
        return "Goodbye, {$name}.";
    }

    protected function secret(): void {}
}
