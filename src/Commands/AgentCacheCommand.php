<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Commands;

use AgentNative\Laravel\Engine\ActionRegistry;
use AgentNative\Laravel\Schema\ActionDefinition;
use Illuminate\Console\Command;

final class AgentCacheCommand extends Command
{
    protected $signature = 'agent:cache';

    protected $description = 'Compile agent tool schemas to disk for zero-reflection production boot';

    public function handle(ActionRegistry $registry): int
    {
        $path = $this->laravel['config']->get('agent-native.cache_path');

        if (! is_string($path)) {
            $this->components->error('agent-native.cache_path is not configured.');

            return self::FAILURE;
        }

        // Force fresh reflection, bypassing any existing cache.
        $definitions = $registry->compileAll();

        $payload = array_map(
            static fn (ActionDefinition $definition) => $definition->toArray(),
            array_values($definitions),
        );

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, '<?php return '.var_export($payload, true).';'.PHP_EOL);

        $this->components->info(sprintf('Agent schemas cached [%d tool(s)] to %s', count($payload), $path));

        return self::SUCCESS;
    }
}
