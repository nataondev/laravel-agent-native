<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Commands;

use AgentNative\Laravel\Schema\SchemaCompiler;
use Illuminate\Console\Command;

final class AgentDiscoverCommand extends Command
{
    protected $signature = 'agent:discover
        {--path=app/Services : Path relative to base_path to scan}
        {--register : Add findings to config/agent-native.php (default: report only)}';

    protected $description = 'Scan for agent actions in a directory and optionally register them in config';

    public function handle(): int
    {
        $scanPath = (string) $this->option('path');
        // Handle both relative and absolute paths
        $fullPath = is_dir($scanPath) ? $scanPath : base_path($scanPath);

        if (! is_dir($fullPath)) {
            $this->components->error("Directory not found: {$fullPath}");

            return self::FAILURE;
        }

        $compiler = new SchemaCompiler;
        $candidates = [];

        foreach ($this->walkPhpFiles($fullPath) as $file) {
            try {
                $fqcn = $this->extractFqcnFromPath($file, $scanPath);

                if (! $fqcn) {
                    continue; // Cannot extract namespace/class
                }

                // Attempt to compile - this will throw if not autoloadable or invalid
                $definitions = $compiler->compile($fqcn);

                if ($definitions !== []) {
                    $actionCount = count($definitions);
                    $candidates[$fqcn] = ['count' => $actionCount, 'file' => str_replace(base_path(), '', $file)];
                }
            } catch (\Throwable) {
                // Skip files that fail to parse/reflect (not autoloadable, etc.)
            }
        }

        if ($candidates === []) {
            $this->components->info("No agent actions found in {$scanPath}.");

            return self::SUCCESS;
        }

        // Report mode (table)
        $rows = array_map(
            static fn ($fqcn, $data) => [$fqcn, $data['count'], $data['file']],
            array_keys($candidates),
            $candidates,
        );

        $this->table(['Class', 'Actions', 'File'], $rows);
        $this->components->info('Found '.count($candidates).' class(es).');

        // Register mode
        if ($this->option('register')) {
            return $this->registerCandidates($candidates);
        }

        return self::SUCCESS;
    }

    private function walkPhpFiles(string $base): \Generator
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $base,
            \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::UNIX_PATHS,
        ));

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }

    private function extractFqcnFromPath(string $path, string $scanPath): ?string
    {
        $content = file_get_contents($path);
        if (! is_string($content)) {
            return null;
        }

        // Parse namespace (use /m for multiline so ^ matches line start)
        $namespaceMatch = [];
        if (! preg_match('/^namespace\s+([\w\\\\]+);/m', trim($content), $namespaceMatch)) {
            return null;
        }
        $namespace = $namespaceMatch[1];

        // Parse final/class
        $classMatch = [];
        if (! preg_match('/(?:final\s+)?class\s+(\w+)/', $content, $classMatch)) {
            return null;
        }
        $class = $classMatch[1];

        return $namespace.'\\'.$class;
    }

    private function registerCandidates(array $candidates): int
    {
        $configPath = config_path('agent-native.php');
        $line = '    ';

        if (! is_file($configPath)) {
            $this->components->warn('config/agent-native.php is not published.');
            $this->components->bulletList([
                'Run: php artisan vendor:publish --tag=agent-native-config',
                'Then add manually under \'classes\': ',
            ]);

            $facades = implode("\n", array_map(
                static fn ($fqcn): string => "    \\{$fqcn}::class,",
                array_keys($candidates),
            ));

            $this->newLine();
            $this->line($facades);

            return self::SUCCESS;
        }

        $contents = (string) file_get_contents($configPath);
        $anchorPattern = '/^(?<indent>[ \t]*)[\'"]classes[\'"]\s*=>\s*\[/m';

        if (! preg_match($anchorPattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $this->components->warn("Could not find the 'classes' => [ anchor in config.");
            $this->components->bulletList(['Add manually under \'classes\': ']);

            $lines = array_map(static fn ($fqcn): string => '\\'.$fqcn.'::class,', array_keys($candidates));
            $this->line(implode("\n", $lines));

            return self::SUCCESS;
        }

        $indent = $matches['indent'][0].'    ';
        $added = 0;

        foreach ($candidates as $fqcn) {
            if (str_contains($contents, $fqcn)) {
                continue;
            }

            $anchorOffset = $matches[0][1];
            $anchorEnd = $anchorOffset + strlen($matches[0][0]);

            $insertedLine = PHP_EOL.$indent."\\{$fqcn}::class,";
            $contents = substr($contents, 0, $anchorEnd).$insertedLine.substr($contents, $anchorEnd);

            $added++;
        }

        file_put_contents($configPath, $contents);
        $this->components->info("Registered {$added} new class(es) in config/agent-native.php.");

        if ($added < count($candidates)) {
            $this->components->info('Already registered: '.(count($candidates) - $added).' class(es).');
        }

        return self::SUCCESS;
    }
}
