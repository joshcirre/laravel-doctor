<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers;

class RectorRunner
{
    public function isAvailable(string $basePath): bool
    {
        return file_exists(rtrim($basePath, '/').'/vendor/bin/rector');
    }

    /**
     * @param  array<int, string>|null  $fileSubset
     */
    public function run(string $basePath, bool $dryRun, ?array $fileSubset = null): int
    {
        $commandParts = [
            'php',
            escapeshellarg('vendor/bin/rector'),
            'process',
            '--ansi',
        ];

        if ($dryRun) {
            $commandParts[] = '--dry-run';
        }

        if (is_array($fileSubset) && $fileSubset !== []) {
            foreach ($fileSubset as $relativePath) {
                $commandParts[] = escapeshellarg($relativePath);
            }
        }

        $command = implode(' ', $commandParts);

        $descriptorSpecification = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        ];

        $process = proc_open($command, $descriptorSpecification, $pipes, $basePath);

        if (! is_resource($process)) {
            return 1;
        }

        return (int) proc_close($process);
    }
}
