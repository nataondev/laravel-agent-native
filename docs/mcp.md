# MCP Server (optional bridge)

Expose every `#[AgentAction]` as a standard [Model Context Protocol](https://modelcontextprotocol.io) tool using the official [`laravel/mcp`](https://laravel.com/docs/mcp) package — no hand-written tool classes, no duplicated schemas. The registry stays the single source of truth; MCP is just another transport beside the HTTP endpoint and the Livewire bridge.

## Why the bridge

| | This package alone | `laravel/mcp` alone | Bridge (both) |
|---|---|---|---|
| Schema | auto from reflection | hand-written per tool | **auto from reflection** |
| Boilerplate | zero | one Tool class per action | **zero** |
| Transport | HTTP + Livewire | MCP (web + stdio) | **all three** |
| Auth | your middleware | OAuth 2.1 / Sanctum | **both** |
| Livewire reactivity | ✅ | — | ✅ |

## Install

```bash
composer require laravel/mcp
php artisan agent:mcp
```

`agent:mcp` creates `routes/ai.php` (if missing) and appends:

```php
Mcp::web('/mcp/agent', \AgentNative\Laravel\Mcp\AgentNativeServer::class)
    ->middleware(['auth:sanctum', 'throttle:60,1']);
```

Options:

```bash
php artisan agent:mcp --path=/mcp/tools   # custom web path
php artisan agent:mcp --local=agent       # also register a stdio server (for local AI clients)
php artisan agent:mcp --force             # re-append even if already registered
```

> **Security:** the generated registration ships with `auth:sanctum` + `throttle`. Keep it — an MCP endpoint executes your application code. See [HTTP endpoint security](http-endpoint.md#-security-do-not-expose-it-raw); the same rules apply.

## How it works

`AgentNativeServer` builds one `AgentNativeTool` per registered action at boot:

- **name / description** — from the compiled `ActionDefinition`.
- **inputSchema** — the exact JSON Schema the `SchemaCompiler` produced (enums, array items, `additionalProperties: false`), exposed verbatim.
- **handle()** — runs through the shared `ActionExecutor`, so validation, container DI, enum coercion, and error codes are identical to the HTTP endpoint and Livewire bridge. Failures map to MCP error responses (`[code] message`).

```mermaid
flowchart LR
    A[#[AgentAction] methods] --> R[ActionRegistry]
    R -->|HTTP| H[AgentToolsController]
    R -->|Livewire| L[AgentBridge]
    R -->|MCP| S[AgentNativeServer]
    S --> T1[AgentNativeTool]
    S --> T2[AgentNativeTool]
    T1 --> E[ActionExecutor]
    T2 --> E
    H --> E
    L --> E
```

## Verifying

```bash
php artisan agent:inspect          # same tools, CLI view
```

Or point an MCP client / the [MCP Inspector](https://laravel.com/docs/mcp#mcp-inspector) at `/mcp/agent` — every registered action appears with its full input schema.

## Conditional exposure

Because each `AgentNativeTool` is a real `Laravel\Mcp\Server\Tool`, you can subclass `AgentNativeServer` and override `shouldRegister`-style logic or filter `$this->tools` in a constructor if you need per-user or per-environment tool sets.

Next: [Artisan commands](artisan-commands.md)
