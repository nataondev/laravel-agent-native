# Executing Actions

The `ActionExecutor` runs an incoming tool call — `{ "name": "...", "arguments": {...} }` — against the registry with full validation and DI.

## Basic usage

```php
use AgentNative\Laravel\Engine\ActionExecutor;

$result = app(ActionExecutor::class)->call('createTask', [
    'title' => 'Ship the MVP',
    'priority' => 'high',   // coerced to Priority::High
]);
```

## ActionResult

Always returned, never throws:

```php
$result->ok;      // bool
$result->action;  // tool name
$result->output;  // JSON-safe return value (on success)
$result->code;    // error code (on failure)
$result->error;   // human/agent-readable message (on failure)

// JsonSerializable — hand straight to the LLM:
return response()->json($result, $result->ok ? 200 : 422);
```

## What the executor guarantees

1. **Container resolution** — the target class is built via `app($class)`, so constructor injection works as usual.
2. **Validation before invocation:**
   - missing required params → `missing_params` (lists each)
   - unknown params → `invalid_param` (parity with `additionalProperties: false`)
   - wrong scalar type → `invalid_param` (readable message)
   - invalid enum value → `invalid_param`
3. **Coercion** — backed enums from scalar values, pure enums from case names, `int` → `float` widening.
4. **Defaults applied** for omitted optional parameters.
5. **Exception capture** — any `Throwable` becomes `execution_error` (and is reported to Laravel's exception handler). The agent never sees a stack trace.
6. **Output normalization** — enums → scalars, `JsonSerializable`/`toArray()`/`Stringable` → JSON-safe values, recursively through arrays.

## Error codes

| Code              | Meaning                                    |
|-------------------|--------------------------------------------|
| `unknown_action`  | Tool name not registered                   |
| `missing_params`  | One or more required arguments absent      |
| `invalid_param`   | Unknown, mistyped, or invalid enum value   |
| `execution_error` | The action itself threw                    |

Agents can retry `missing_params`/`invalid_param` with corrected arguments; `execution_error` signals a server-side problem.

## Getting the tool list for an LLM

```php
use AgentNative\Laravel\Engine\ActionRegistry;

$tools = app(ActionRegistry::class)->tools(); // OpenAI tool schema array
```

Next: [Livewire integration](livewire.md)
