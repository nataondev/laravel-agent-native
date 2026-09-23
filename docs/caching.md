# Caching

Reflection is dev-friendly but shouldn't run per-request in production. `agent:cache` compiles every registered class's tool definitions into a plain PHP return-array file.

## How it works

1. `php artisan agent:cache` reflects all classes in `config/agent-native.php` and writes `bootstrap/cache/agent-native.php` (path configurable via `cache_path`).
2. On boot, `ActionRegistry` checks for the file. Present → definitions load from disk, **no reflection**.
3. Absent → definitions compile on demand (dev mode).

## Deploy recipe

```bash
php artisan config:cache
php artisan agent:cache
```

## Invalidation

The cache is a static snapshot. Re-run `agent:cache` whenever you:

- add/remove action classes in config,
- change any `#[AgentAction]`/`#[AgentParam]` metadata,
- change an action method's signature.

Or clear it to fall back to live reflection:

```bash
php artisan agent:clear
```

## Verifying

A cached registry serves tools even with no classes registered — the file is the source of truth:

```php
$registry = new ActionRegistry(new SchemaCompiler, $cachePath);
$registry->tools(); // served entirely from disk
```
