<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Engine;

use AgentNative\Laravel\Schema\ActionDefinition;
use BackedEnum;
use Illuminate\Contracts\Container\Container;
use JsonSerializable;
use Stringable;
use Throwable;
use UnitEnum;

/**
 * Executes an incoming agent tool call against the registry:
 *
 *   { "name": "addToCart", "arguments": { "productId": 42 } }
 *
 * Validation is performed up-front so the agent receives structured,
 * retry-able feedback instead of a PHP TypeError stack trace.
 */
final class ActionExecutor
{
    public function __construct(
        private readonly ActionRegistry $registry,
        private readonly Container $container,
    ) {}

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function call(string $name, array $arguments = []): ActionResult
    {
        $definition = $this->registry->find($name);

        if (! $definition instanceof ActionDefinition) {
            return ActionResult::error($name, 'unknown_action', "No agent action named [{$name}] is registered.");
        }

        $validated = $this->validate($definition, $arguments);

        if ($validated instanceof ActionResult) {
            return $validated;
        }

        try {
            $instance = $this->container->make($definition->class);
            $output = $instance->{$definition->method}(...$validated);

            return ActionResult::success($name, $this->normalize($output));
        } catch (Throwable $e) {
            report($e);

            return ActionResult::error($name, 'execution_error', $e->getMessage());
        }
    }

    /**
     * Validate + coerce incoming arguments into positional call arguments.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<int, mixed>|ActionResult
     */
    private function validate(ActionDefinition $definition, array $arguments): array|ActionResult
    {
        $missing = array_diff($definition->required, array_keys($arguments));
        if ($missing !== []) {
            return ActionResult::error(
                $definition->name,
                'missing_params',
                'Missing required parameter(s): '.implode(', ', $missing),
            );
        }

        $extra = array_diff(array_keys($arguments), array_keys($definition->properties));
        if ($extra !== []) {
            return ActionResult::error(
                $definition->name,
                'invalid_param',
                'Unknown parameter(s): '.implode(', ', $extra),
            );
        }

        $method = new \ReflectionMethod($definition->class, $definition->method);
        $call = [];

        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (! array_key_exists($name, $arguments)) {
                $call[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;

                continue;
            }

            $coerced = $this->coerce($parameter, $arguments[$name]);

            if ($coerced instanceof ActionResult) {
                return $coerced;
            }

            $call[] = $coerced;
        }

        return $call;
    }

    /**
     * Coerce one argument to the parameter's declared type.
     */
    private function coerce(\ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if ($value === null) {
            return $parameter->allowsNull() ? null : ActionResult::error(
                $parameter->getDeclaringFunction()->getName(),
                'invalid_param',
                "Parameter [\${$parameter->getName()}] must not be null.",
            );
        }

        if (! $type instanceof \ReflectionNamedType) {
            return $value; // unions/intersections/untyped: pass through (MVP)
        }

        $typeName = $type->getName();
        $param = '$'.$parameter->getName();

        if (enum_exists($typeName)) {
            return $this->coerceEnum($typeName, $value, $param, $parameter);
        }

        $valid = match ($typeName) {
            'string' => is_string($value),
            'int' => is_int($value),
            'float' => is_float($value) || is_int($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            default => true, // object types: pass through
        };

        if (! $valid) {
            return ActionResult::error(
                $parameter->getDeclaringFunction()->getName(),
                'invalid_param',
                "Parameter [{$param}] must be of type {$typeName}, ".get_debug_type($value).' given.',
            );
        }

        return $typeName === 'float' ? (float) $value : $value;
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     */
    private function coerceEnum(string $enum, mixed $value, string $param, \ReflectionParameter $parameter): UnitEnum|ActionResult
    {
        $error = ActionResult::error(
            $parameter->getDeclaringFunction()->getName(),
            'invalid_param',
            "Parameter [{$param}] is not a valid case of {$enum}.",
        );

        if (is_subclass_of($enum, BackedEnum::class)) {
            if (! is_int($value) && ! is_string($value)) {
                return $error;
            }

            return $enum::tryFrom($value) ?? $error;
        }

        // Pure enum: match by case name.
        if (! is_string($value)) {
            return $error;
        }

        foreach ($enum::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return $error;
    }

    /**
     * Normalize any return value to a JSON-safe payload for the agent.
     */
    private function normalize(mixed $output): mixed
    {
        return match (true) {
            $output instanceof BackedEnum => $output->value,
            $output instanceof UnitEnum => $output->name,
            $output instanceof JsonSerializable => $output->jsonSerialize(),
            $output instanceof Stringable => (string) $output,
            is_array($output) => array_map(fn (mixed $item) => $this->normalize($item), $output),
            is_object($output) && method_exists($output, 'toArray') => $output->toArray(),
            default => $output,
        };
    }
}
