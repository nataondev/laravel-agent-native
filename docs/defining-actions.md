# Defining Actions

An action is any public method annotated with `#[AgentAction]`. The same method serves your UI **and** the agent — one definition, no duplicated schema.

## Basic action

```php
use AgentNative\Laravel\Attributes\AgentAction;

class TaskService
{
    #[AgentAction(description: 'Create a task in the backlog')]
    public function createTask(string $title, ?int $estimateHours = null): Task
    {
        // ...
    }
}
```

The compiler reads the signature and produces:

```json
{
  "type": "function",
  "function": {
    "name": "createTask",
    "description": "Create a task in the backlog",
    "parameters": {
      "type": "object",
      "properties": {
        "title": { "type": "string" },
        "estimateHours": { "type": "integer" }
      },
      "required": ["title"],
      "additionalProperties": false
    }
  }
}
```

## Custom tool names

Default name = method name. Override for agent-friendly naming:

```php
#[AgentAction(description: 'Rename a task', name: 'rename_task')]
public function rename(int $id, string $title): string { ... }
```

## Parameter metadata with `#[AgentParam]`

```php
use AgentNative\Laravel\Attributes\AgentParam;

public function createTask(
    string $title,
    #[AgentParam(description: 'Priority level for triage')]
    Priority $priority = Priority::Medium,
): Task { ... }
```

### Description precedence

1. `#[AgentParam(description: '...')]`
2. Docblock `@param string $title The task title`
3. Omitted

### Required precedence

1. `#[AgentParam(required: true|false)]` — explicit override
2. Inferred from the signature: **no default value AND not nullable → required**

`required` is tri-state (`?bool = null`): passing only a description never changes inferred requiredness.

## Docblocks as descriptions

No attribute needed when the docblock already says it:

```php
/**
 * @param string $title The task title
 * @param string[] $tags Labels to attach
 */
#[AgentAction(description: 'Create a task')]
public function createTask(string $title, array $tags = []): Task { ... }
```

## Whole-class exposure with `#[AgentExpose]`

Expose every public method on the class (great for agent-first services):

```php
use AgentNative\Laravel\Attributes\AgentExpose;

#[AgentExpose]
class ReportingService
{
    /** Revenue for a given date. */
    public function dailyRevenue(string $date): float { ... }  // description from docblock
}
```

- Methods with explicit `#[AgentAction]` keep their metadata.
- `protected`/`private`/static/inherited methods are never exposed.
- Magic methods (`__*`) are skipped.

Next: [Type mapping](type-mapping.md)
