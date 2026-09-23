<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Schema;

use AgentNative\Laravel\Attributes\AgentAction;
use AgentNative\Laravel\Attributes\AgentExpose;
use AgentNative\Laravel\Attributes\AgentParam;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use UnitEnum;

/**
 * Compiles #[AgentAction]-annotated methods into OpenAI/MCP tool schemas
 * using pure PHP reflection. Zero third-party schema dependencies.
 */
final class SchemaCompiler
{
    /**
     * Compile every agent action on the given class into definitions.
     *
     * @param  class-string  $class
     * @return list<ActionDefinition>
     */
    public function compile(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $exposed = $reflection->getAttributes(AgentExpose::class) !== [];

        $definitions = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue; // skip inherited framework plumbing (e.g. Livewire Component methods)
            }

            $attributes = $method->getAttributes(AgentAction::class);

            if ($attributes === []) {
                if (! $exposed || str_starts_with($method->getName(), '__')) {
                    continue;
                }

                // Whole-class exposure: synthesize default metadata.
                $action = new AgentAction(description: $this->firstDocblockLine($method) ?: $method->getName());
            } else {
                $action = $attributes[0]->newInstance();
            }

            $definitions[] = $this->compileMethod($method, $action);
        }

        return $definitions;
    }

    private function compileMethod(ReflectionMethod $method, AgentAction $action): ActionDefinition
    {
        $docParams = $this->parseDocblockParams($method);

        $properties = [];
        $required = [];

        foreach ($method->getParameters() as $parameter) {
            $param = $this->paramAttribute($parameter);
            $name = $parameter->getName();

            $schema = $this->schemaForParameter($parameter, $docParams[$name]['type'] ?? null);

            $description = $param?->description ?: ($docParams[$name]['description'] ?? '');
            if ($description !== '') {
                $schema['description'] = $description;
            }

            $properties[$name] = $schema;

            // Requiredness: explicit attribute choice wins; otherwise infer
            // (parameter without default and not nullable => required).
            $isRequired = $param?->required
                ?? (! $parameter->isDefaultValueAvailable() && ! $parameter->allowsNull());

            if ($isRequired) {
                $required[] = $name;
            }
        }

        return new ActionDefinition(
            name: $action->name ?? $method->getName(),
            description: $action->description,
            class: $method->getDeclaringClass()->getName(),
            method: $method->getName(),
            properties: $properties,
            required: $required,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaForParameter(ReflectionParameter $parameter, ?string $docType): array
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            return $this->schemaForNamedType($type, $docType);
        }

        if ($type instanceof ReflectionUnionType) {
            // nullable union (T|null) handled by allowsNull at requiredness level;
            // MVP: use the first non-null member.
            foreach ($type->getTypes() as $member) {
                if ($member instanceof ReflectionNamedType && $member->getName() !== 'null') {
                    return $this->schemaForNamedType($member, $docType);
                }
            }
        }

        if ($type instanceof ReflectionIntersectionType) {
            return ['type' => 'object'];
        }

        // Untyped parameter: accept anything.
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaForNamedType(ReflectionNamedType $type, ?string $docType): array
    {
        $name = $type->getName();

        return match (true) {
            $name === 'string' => ['type' => 'string'],
            $name === 'int' => ['type' => 'integer'],
            $name === 'float' => ['type' => 'number'],
            $name === 'bool' => ['type' => 'boolean'],
            $name === 'array' => $this->arraySchema($docType),
            $name === 'null' => ['type' => 'null'],
            enum_exists($name) => $this->enumSchema($name),
            default => ['type' => 'object'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function arraySchema(?string $docType): array
    {
        $schema = ['type' => 'array'];

        // Support "string[]", "int[]", "float[]", "bool[]" docblock hints.
        if ($docType !== null && preg_match('/^(string|int|float|bool)\[\]$/', $docType, $m) === 1) {
            $schema['items'] = match ($m[1]) {
                'string' => ['type' => 'string'],
                'int' => ['type' => 'integer'],
                'float' => ['type' => 'number'],
                'bool' => ['type' => 'boolean'],
            };
        }

        return $schema;
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     * @return array<string, mixed>
     */
    private function enumSchema(string $enum): array
    {
        $reflection = new ReflectionClass($enum);

        if ($reflection->implementsInterface(\BackedEnum::class)) {
            $values = array_map(static fn (\BackedEnum $case) => $case->value, $enum::cases());
            $type = $values === [] || is_int($values[0]) ? 'integer' : 'string';

            return ['type' => $type, 'enum' => $values];
        }

        // Pure enum: expose case names as string enum.
        return ['type' => 'string', 'enum' => array_map(static fn (UnitEnum $case) => $case->name, $enum::cases())];
    }

    private function paramAttribute(ReflectionParameter $parameter): ?AgentParam
    {
        $attributes = $parameter->getAttributes(AgentParam::class);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    /**
     * Extract @param tags: name => ['type' => ?, 'description' => ?]
     *
     * @return array<string, array{type: ?string, description: string}>
     */
    private function parseDocblockParams(ReflectionMethod $method): array
    {
        $doc = $method->getDocComment();
        if ($doc === false) {
            return [];
        }

        $params = [];
        if (preg_match_all('/@param\s+(\S+)\s+\$(\w+)[ \t]*([^\r\n*]*)/', $doc, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($matches as $match) {
            $params[$match[2]] = [
                'type' => $match[1] !== 'mixed' ? $match[1] : null,
                'description' => trim($match[3] ?? ''),
            ];
        }

        return $params;
    }

    private function firstDocblockLine(ReflectionMethod $method): ?string
    {
        $doc = $method->getDocComment();
        if ($doc === false) {
            return null;
        }

        // Single-line: /** Greet a person by name. */
        if (preg_match('/^\/\*\*[ \t]+([^@\s*].*?)[ \t]*\*\/$/s', trim($doc), $m) === 1) {
            return trim($m[1]);
        }

        // Multi-line: first non-empty, non-tag line.
        if (preg_match('/^[ \t]*\*[ \t]+([^@\s{\/].*)$/m', $doc, $m) === 1) {
            return trim($m[1]);
        }

        return null;
    }
}
