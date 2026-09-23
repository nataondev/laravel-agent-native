# Artisan Commands

## `agent:inspect`

List every registered tool — names, handlers, descriptions, parameters:

```bash
php artisan agent:inspect
```

```
+-------------+------------------------------+------------------------------+--------------------------------+
| Tool        | Handler                      | Description                  | Parameters                     |
+-------------+------------------------------+------------------------------+--------------------------------+
| createTask  | App\Services\TaskService@... | Create a task in the backlog | title: string, priority: ...?  |
| rename_task | App\Services\TaskService@... | Rename an existing task      | id: integer, title: string     |
+-------------+------------------------------+------------------------------+--------------------------------+
```

Dump the raw JSON schemas (pipe into your LLM/MCP config):

```bash
php artisan agent:inspect --json
```

## `agent:cache`

Compile all tool schemas to `bootstrap/cache/agent-native.php`:

```bash
php artisan agent:cache
```

With the cache present the registry skips reflection entirely — zero-cost production boot. Add it to your deploy script after `config:cache`.

## `agent:clear`

Remove the compiled schema cache:

```bash
php artisan agent:clear
```

## `agent:mcp`

Wire the optional [`laravel/mcp`](https://laravel.com/docs/mcp) bridge — registers the `AgentNativeServer` in `routes/ai.php` with a safe middleware default. See [MCP server](mcp.md).

```bash
php artisan agent:mcp                  # web server at /mcp/agent
php artisan agent:mcp --local=agent    # also register a stdio server
php artisan agent:mcp --path=/mcp/x    # custom web path
```

Fails with a clear message if `laravel/mcp` is not installed.

Next: [Caching](caching.md)
