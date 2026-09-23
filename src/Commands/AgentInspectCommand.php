<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Commands;

use AgentNative\Laravel\Engine\ActionRegistry;
use Illuminate\Console\Command;

final class AgentInspectCommand extends Command
{
    protected $signature = 'agent:inspect {--json : Dump raw tool schemas as JSON}';

    protected $description = 'List all registered agent actions and their generated tool schemas';

    public function handle(ActionRegistry $registry): int
    {
        $definitions = $registry->all();

        if ($definitions === []) {
            $this->components->warn('No agent actions registered. Add classes to config/agent-native.php.');

            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->line(json_encode($registry->tools(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $rows = array_map(static fn ($definition) => [
            $definition->name,
            $definition->class.'@'.$definition->method,
            $definition->description,
            implode(', ', array_map(
                static fn (string $param, array $schema) => $param.': '.($schema['type'] ?? 'mixed').(in_array($param, $definition->required, true) ? '' : '?'),
                array_keys($definition->properties),
                $definition->properties,
            )) ?: '—',
        ], array_values($definitions));

        $this->table(['Tool', 'Handler', 'Description', 'Parameters'], $rows);

        return self::SUCCESS;
    }
}
