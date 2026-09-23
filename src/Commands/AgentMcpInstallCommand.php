<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Commands;

use Illuminate\Console\Command;
use Laravel\Mcp\Server;

/**
 * Scaffolds the optional laravel/mcp integration: ensures routes/ai.php
 * exists and appends an AgentNativeServer registration with a safe default
 * middleware stack.
 */
final class AgentMcpInstallCommand extends Command
{
    protected $signature = 'agent:mcp 
                            {--path=/mcp/agent : The web endpoint path}
                            {--local= : Also register a local (stdio) server with this name}
                            {--force : Overwrite an existing registration}';

    protected $description = 'Wire the optional laravel/mcp bridge: register the AgentNativeServer in routes/ai.php';

    public function handle(): int
    {
        if (! class_exists(Server::class)) {
            $this->components->error('laravel/mcp is not installed. Run: composer require laravel/mcp');

            return self::FAILURE;
        }

        $routesPath = base_path('routes/ai.php');

        if (! is_file($routesPath)) {
            $this->publishRoutesFile($routesPath);
        }

        $path = (string) $this->option('path');
        $registration = $this->registrationSnippet($path);

        $contents = file_get_contents($routesPath);

        if (str_contains($contents, 'AgentNativeServer::class') && ! $this->option('force')) {
            $this->components->warn('AgentNativeServer already registered in routes/ai.php (use --force to add another).');

            return self::SUCCESS;
        }

        file_put_contents($routesPath, rtrim($contents).PHP_EOL.PHP_EOL.$registration.PHP_EOL);

        $this->components->info("AgentNative MCP server registered at [{$path}] in routes/ai.php");
        $this->components->bulletList([
            'Protect it: adjust the middleware in routes/ai.php (auth:sanctum + throttle recommended).',
            'Inspect it:  php artisan mcp:inspector (or your MCP client) against '.$path,
        ]);

        return self::SUCCESS;
    }

    private function publishRoutesFile(string $routesPath): void
    {
        $stub = <<<'PHP'
        <?php

        use Laravel\Mcp\Facades\Mcp;

        PHP;

        if (! is_dir(dirname($routesPath))) {
            mkdir(dirname($routesPath), 0755, true);
        }

        file_put_contents($routesPath, $stub);
    }

    private function registrationSnippet(string $path): string
    {
        $pathExport = var_export($path, true);

        $snippet = <<<PHP
        Mcp::web({$pathExport}, \\AgentNative\\Laravel\\Mcp\\AgentNativeServer::class)
            ->middleware(['auth:sanctum', 'throttle:60,1']);
        PHP;

        $local = $this->option('local');
        if (is_string($local) && $local !== '') {
            $localExport = var_export($local, true);
            $snippet .= PHP_EOL.PHP_EOL."Mcp::local({$localExport}, \\AgentNative\\Laravel\\Mcp\\AgentNativeServer::class);";
        }

        return $snippet;
    }
}
