<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Registered action classes
    |--------------------------------------------------------------------------
    |
    | Classes listed here are scanned for #[AgentAction] methods (or exposed
    | wholesale via #[AgentExpose]) and registered as agent tools at boot.
    |
    */

    'classes' => [
        // App\Services\TaskService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema cache
    |--------------------------------------------------------------------------
    |
    | When `php artisan agent:cache` has run, compiled tool definitions are
    | loaded from this file and reflection is skipped entirely.
    |
    */

    'cache_path' => base_path('bootstrap/cache/agent-native.php'),

    /*
    |--------------------------------------------------------------------------
    | HTTP tool endpoint
    |--------------------------------------------------------------------------
    |
    | The package does NOT register routes for you — mount AgentToolsController
    | under your own middleware. These settings harden the controller itself
    | regardless of how routes are wired.
    |
    |   enabled:      master switch; when false the endpoint returns 403.
    |   authorize:    optional callable (class@method or closure string)
    |                 invoked per request; return false/throw to deny.
    |
    | ALWAYS protect the routes: auth (Sanctum/session), authorization
    | (Gate/policy per action), and throttle. See docs/http-endpoint.md.
    |
    */

    'http' => [
        'enabled' => env('AGENT_NATIVE_HTTP', true),
        'authorize' => null,
    ],

];
