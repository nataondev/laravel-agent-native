# Type Mapping

Native PHP types compile to JSON Schema. No third-party schema library.

| PHP type              | JSON Schema                                            |
|-----------------------|--------------------------------------------------------|
| `string`              | `{"type": "string"}`                                   |
| `int`                 | `{"type": "integer"}`                                  |
| `float`               | `{"type": "number"}`                                   |
| `bool`                | `{"type": "boolean"}`                                  |
| `array`               | `{"type": "array"}`                                    |
| `array` + `@param string[] $x` | `{"type": "array", "items": {"type": "string"}}` |
| backed enum           | `{"type": <backing type>, "enum": [...values]}`        |
| pure enum             | `{"type": "string", "enum": [...case names]}`          |
| `?T`                  | schema of `T`; parameter becomes optional              |
| class type            | `{"type": "object"}` (opaque)                          |
| no type               | `{}` (accepts anything)                                |

## Arrays with item types

Supported docblock hints: `string[]`, `int[]`, `float[]`, `bool[]`.

```php
/** @param int[] $ids */
#[AgentAction(description: 'Delete tasks in bulk')]
public function deleteMany(array $ids): int { ... }
```

→ `"ids": {"type": "array", "items": {"type": "integer"}}`

## Enums

Backed enums compile to their backing type with an `enum` constraint:

```php
enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}

public function createTask(string $title, Priority $priority = Priority::Medium): Task
```

→ `"priority": {"type": "string", "enum": ["low", "medium", "high"]}`

Pure (non-backed) enums use case **names**:

```php
enum Status { case Open; case Closed; }
```

→ `"status": {"type": "string", "enum": ["Open", "Closed"]}`

At execution time the executor coerces the scalar back into the enum instance — your method always receives the real enum.

## Nullability and defaults

Both make a parameter optional (absent from `required`):

```php
public function find(?string $query = null, int $limit = 20): array
```

→ `"required": []`

## Unions

`T|null` unions resolve to `T`. Broader unions pass through unvalidated (MVP limitation).

Next: [Executing actions](executing-actions.md)
