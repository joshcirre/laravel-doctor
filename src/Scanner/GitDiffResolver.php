<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scanner;

class GitDiffResolver
{
    /**
     * @return array<int, string>|null
     */
    public function resolveChangedPhpFiles(string $basePath, ?string $baseBranch): ?array
    {
        $targetBase = $baseBranch ?? $this->detectBaseBranch($basePath);

        if ($targetBase === null) {
            return null;
        }

        $command = sprintf('git diff --name-only %s...HEAD', escapeshellarg($targetBase));
        $output = $this->runCommand($command, $basePath);

        if ($output === null) {
            return null;
        }

        $lines = preg_split('/\R/', trim($output)) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $lines
        ), static fn (string $line): bool => $line !== '' && str_ends_with($line, '.php')));
    }

    private function detectBaseBranch(string $basePath): ?string
    {
        foreach (['main', 'master'] as $candidate) {
            $output = $this->runCommand(sprintf('git rev-parse --verify %s', escapeshellarg($candidate)), $basePath);

            if ($output !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private function runCommand(string $command, string $workingDirectory): ?string
    {
        $descriptorSpecification = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpecification, $pipes, $workingDirectory);

        if (! is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            return null;
        }

        return is_string($stdout) && $stderr !== false ? $stdout : null;
    }
}
