<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Facades;

use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Engine\ActionResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ActionRegistry register(string $class)
 * @method static array<int, array<string, mixed>> tools()
 * @method static ActionResult call(string $action, array $arguments = [])
 * @method static ActionResult run(string $action, array $arguments = [])
 *
 * @see ActionRegistry
 */
final class AgentNative extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActionRegistry::class;
    }
}
