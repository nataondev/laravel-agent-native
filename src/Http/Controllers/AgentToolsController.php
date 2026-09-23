<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Http\Controllers;

use AgentNative\Laravel\Engine\ActionExecutor;
use AgentNative\Laravel\Engine\ActionRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tool-call endpoint: GET lists OpenAI tool schemas, POST executes a tool
 * call `{ "name": "...", "arguments": { ... } }`.
 *
 * Security model:
 *  - Routes are registered by the CONSUMER — always under auth + throttle
 *    middleware (see docs/http-endpoint.md).
 *  - `agent-native.http.enabled` is a master kill switch (403 when off).
 *  - `agent-native.http.authorize` optionally points at a callable
 *    (`Class@method` or invokable class) that receives the Request and the
 *    requested action name; returning false (or throwing) denies with 403.
 */
final class AgentToolsController extends Controller
{
    public function index(ActionRegistry $registry): JsonResponse
    {
        $this->guard(request());

        return new JsonResponse(['tools' => $registry->tools()]);
    }

    public function store(Request $request, ActionExecutor $executor): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'arguments' => ['sometimes', 'array'],
        ]);

        $this->guard($request, $validated['name']);

        $result = $executor->call($validated['name'], $validated['arguments'] ?? []);

        return new JsonResponse($result, $result->ok ? 200 : 422);
    }

    private function guard(Request $request, ?string $action = null): void
    {
        $config = $request->attributes->get('agent-native.config')
            ?? app('config')->get('agent-native.http', []);

        if (($config['enabled'] ?? true) !== true) {
            throw new AccessDeniedHttpException('Agent tool endpoint is disabled.');
        }

        $authorize = $config['authorize'] ?? null;

        if ($authorize === null) {
            return;
        }

        $allowed = $this->resolveAuthorizer($authorize, $request, $action);

        if ($allowed === false) {
            throw new AccessDeniedHttpException("Not authorized to run agent action [{$action}].");
        }
    }

    private function resolveAuthorizer(mixed $authorize, Request $request, ?string $action): mixed
    {
        $container = app(Container::class);

        if (is_string($authorize) && str_contains($authorize, '@')) {
            [$class, $method] = explode('@', $authorize, 2);

            return $container->make($class)->{$method}($request, $action);
        }

        if (is_string($authorize) && class_exists($authorize)) {
            return $container->make($authorize)($request, $action);
        }

        if (is_callable($authorize)) {
            return $authorize($request, $action);
        }

        return true;
    }
}
