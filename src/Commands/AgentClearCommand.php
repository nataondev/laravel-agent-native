<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Commands;

use Illuminate\Console\Command;

final class AgentClearCommand extends Command
{
    protected $signature = 'agent:clear';

    protected $description = 'Remove the compiled agent schema cache';

    public function handle(): int
    {
        $path = $this->laravel['config']->get('agent-native.cache_path');

        if (is_string($path) && is_file($path)) {
            unlink($path);
            $this->components->info('Agent schema cache cleared.');
        } else {
            $this->components->info('No agent schema cache found.');
        }

        return self::SUCCESS;
    }
}
