# laravel-agent-native — Architecture Specification

## Thesis (from builderio/agent-native)

Define each capability **once**. The same definition powers:

- the UI (Livewire component calls it directly),
- the LLM agent (auto-exposed as an OpenAI/MCP tool),
- artisan/CLI inspection.

The PHP ecosystem currently forces a "manual tool registry": duplicate schema,
types, and descriptions in discrete tool classes. This package eliminates the
duplication via PHP 8 attributes + reflection.

## Mapping: TypeScript → PHP

| agent-native (TS)          | laravel-agent-native (PHP)                              |
|----------------------------|----------------------------------------------------------|
| `defineAction({...})`      | `#[AgentAction]` on a public method                      |
| `schema: z.object({...})`  | Native types + `#[AgentParam]` + docblocks → JSON Schema |
| `run: async ({...}) => {}` | The annotated method body itself                        |
| Action registry            | `ActionRegistry` (class+method pairs, cached)            |
| Tool dispatcher            | `ActionExecutor` (container-resolved invocation)         |
| UI reactivity              | Livewire events via `AgentBridge` / `InteractsWithAgent` |

## Components

### 1. Attributes (`src/Attributes/`)

- `#[AgentAction(description, name: null)]` — TARGET_METHOD. Marks a public
  method as an agent-invokable tool. `name` defaults to the method name.
- `#[AgentParam(description, required: true)]` — TARGET_PARAMETER. Overrides
  description/requiredness; requiredness otherwise inferred from the type
  (nullable or has default → optional).
- `#[AgentExpose]` — TARGET_CLASS. Opt-in marker allowing whole-class scanning
  (every public method becomes an action unless attributed otherwise). MVP:
  used by discovery, optional.

### 2. Schema Engine (`src/Schema/`)

`SchemaCompiler::compile(string $class): array<ToolDefinition>`

- Reflects every method carrying `#[AgentAction]`.
- Builds OpenAI tool-calling format:
  `{type:"function", function:{name, description, parameters:{type:"object", properties, required}}}`
- Type mapping (ReflectionNamedType / ReflectionUnionType):
  - `string` → `{"type":"string"}`
  - `int` → `{"type":"integer"}`
  - `float` → `{"type":"number"}`
  - `bool` → `{"type":"boolean"}`
  - `array` → `{"type":"array"}` (+ `items` from docblock `@param string[] $x`)
  - Backed enum → `{"type": <backing>, "enum": [...values]}`
  - `?T` → type of T, param optional
  - Class type → `{"type":"object"}` (MVP: opaque object)
- Description precedence: `#[AgentParam]` > docblock `@param` > `null`.
- Required: `#[AgentParam(required:…)` > (no default && not nullable).

### 3. Execution Engine (`src/Engine/`)

`ActionRegistry`

- `register(string $class)`, `registerMany(iterable)`
- `tools(): array` — compiled schemas (via cache when enabled)
- `find(string $name): ?ActionDefinition` (class + method + schema)

`ActionExecutor::call(string $name, array $arguments): ActionResult`

- Resolves definition from registry; unknown → `ActionResult::error`.
- Validates: missing required params → error listing them; unknown params
  rejected (OpenAI `additionalProperties:false` parity).
- Coerces/validates scalar types & backed enums (`BackingEnum::from`), with
  clear validation messages instead of TypeErrors.
- Resolves the class through the Laravel container (`app($class)`) → full DI.
- Invokes, catches `Throwable` → `ActionResult::error` (message + class),
  never leaks raw exceptions to the agent loop.
- Result: `ActionResult{ok, name, output, error?}`; `output` normalized to
  JSON-safe (scalars/array/JsonSerializable/Stringable/enum).

### 4. Livewire Bridge (`src/Livewire/`)

`InteractsWithAgent` trait for Livewire components:

- `bootInteractsWithAgent()`: registers the component class with the registry.
- `agentContext(): array` — overridable; exposes current component state
  (public props) to the agent (mirrors agent-native "shared application state").

`AgentBridge` (dispatcher):

- `dispatchToComponent(string $componentId, string $action, array $args)` —
  finds the Livewire component instance and calls the method, then emits a
  `agent-action-performed` browser event so the UI re-renders.
- `broadcast(string $action, array $args)` — global path: executes the action
  statelessly and dispatches a Livewire global event (`Livewire::dispatch`)
  that any listening component can react to.

MVP scope: stateless execution + event broadcast + component trait context.
Component-ID targeting is supported when a component instance is resolvable
in the current request lifecycle.

### 5. CLI (`src/Commands/`)

- `agent:inspect` — table of registered tools (name, class@method, params),
  `--json` dumps the raw tool schema array.
- `agent:cache` — compiles all registered schemas to
  `bootstrap/cache/agent-native.php` (return-array PHP file). Registry loads
  from cache when present (zero reflection in production).
- `agent:clear` — deletes the cache file.

### 6. Service Provider

`AgentNativeServiceProvider`:

- Merges config `agent-native.php` (`classes`, `cache_path`, `livewire` opts).
- Singletons: `ActionRegistry`, `SchemaCompiler`, `ActionExecutor`, `AgentBridge`.
- Auto-registers classes from config + (optionally) auto-discovery path scan.
- Registers commands when running in console.

## Error Model

All failures surface as `ActionResult::error(code, message)`:

- `unknown_action`, `missing_params`, `invalid_param`, `execution_error`.

The agent receives structured, retry-able feedback; humans see the same via
`agent:inspect` / Livewire events.

## Testing Strategy (Pest)

- Schema: scalars, nullable, defaults, backed enums, arrays, docblock
  descriptions, attribute overrides, name override.
- Executor: happy path via container (constructor DI), missing/invalid/extra
  params, enum coercion, exception capture.
- Livewire: test component with trait; action call dispatches event and
  mutates state.
- Commands: `agent:inspect` output, `agent:cache` writes file, registry reads
  from cache without reflection.
