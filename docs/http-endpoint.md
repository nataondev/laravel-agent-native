# HTTP Endpoint

The package ships `AgentToolsController` but **never registers routes itself** — you mount it, which means you own the middleware stack. Treat it like any privileged API surface.

## ⚠️ Security: do not expose it raw

A tool endpoint lets a caller **execute your application code**. The package enforces argument validation, but authentication, authorization, and rate limiting are your middleware stack's job. Minimum recommended stack:

```php
use AgentNative\Laravel\Http\Controllers\AgentToolsController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/agent/tools', [AgentToolsController::class, 'index']);
    Route::post('/agent/tools', [AgentToolsController::class, 'store']);
});
```

**Without `auth:*`, anyone who can reach the URL can invoke every registered action** — including destructive ones.

### What the package handles vs. what you must handle

| Concern | Package | You (consumer) |
|---|---|---|
| Request shape validation | ✅ `name` string, `arguments` array | — |
| Argument validation (missing/unknown/type/enum) | ✅ via `ActionExecutor` | — |
| Unknown action names | ✅ rejected (`unknown_action`) | — |
| Mass-assignment / extra fields | ✅ rejected (`additionalProperties` parity) | — |
| Exception → stack-trace leak | ✅ captured, reported, generic `execution_error` | — |
| **Authentication** | ❌ | ✅ `auth:sanctum` / session |
| **Per-action authorization** | ⚙️ hook (below) | ✅ Gate/policy |
| **Rate limiting** | ❌ | ✅ `throttle:` |
| Injection inside the action body | — | ✅ your implementation (Eloquent/parameterized queries) |

## Kill switch & authorization hook

The controller itself honors two config keys (`config/agent-native.php`), so it stays safe even if routes are mis-wired:

```php
'http' => [
    // Master switch — 403 for every request when false.
    'enabled' => env('AGENT_NATIVE_HTTP', true),

    // Optional per-request authorizer: 'Class@method' or invokable class.
    // Receives ($request, $actionName); return false or throw to deny.
    'authorize' => App\Http\AgentAuthorizer::class.'@authorize',
],
```

Example per-action authorization with a Gate:

```php
namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AgentAuthorizer
{
    public function authorize(Request $request, ?string $action): bool
    {
        // Deny mutating tools for non-admins, allow read-only tools.
        return Gate::forUser($request->user())->allows('run-agent-action', $action);
    }
}
```

## `GET /agent/tools`

Returns the OpenAI tool schema array — point your agent runtime at it:

```json
{ "tools": [ { "type": "function", "function": { "name": "createTask", "...": "..." } } ] }
```

## `POST /agent/tools`

Executes a tool call:

```json
// Request
{ "name": "createTask", "arguments": { "title": "Ship the MVP", "priority": "high" } }

// 200 OK
{ "ok": true, "action": "createTask", "output": { "id": 12, "title": "Ship the MVP" } }
```

Validation failures return `422` with a structured error the agent can act on:

```json
// 422 Unprocessable Entity
{ "ok": false, "action": "createTask", "error": "Missing required parameter(s): title", "code": "missing_params" }
```

Authorization failures return `403`. See [Executing actions](executing-actions.md#error-codes) for all error codes.

## Production checklist

- [ ] Routes behind `auth:sanctum` (or session auth) — never public
- [ ] `throttle:` middleware applied
- [ ] `authorize` hook wired to a Gate/policy for per-action control
- [ ] `AGENT_NATIVE_HTTP=false` in environments where the endpoint must be dead
- [ ] Actions themselves are idempotent / safe to retry (agents retry on `execution_error`)

Next: [Artisan commands](artisan-commands.md)
