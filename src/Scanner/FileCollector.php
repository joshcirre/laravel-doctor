<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scanner;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileCollector
{
    /**
     * @param  array<int, string>|null  $relativeFileSubset
     * @param  array<int, string>  $ignorePathPatterns
     * @return array<int, ProjectFile>
     */
    public function collect(string $basePath, ?array $relativeFileSubset, array $ignorePathPatterns): array
    {
        $relativeSubsetLookup = $relativeFileSubset === null
            ? null
            : array_fill_keys(array_map(static fn (string $path): string => trim($path, '/'), $relativeFileSubset), true);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $projectFiles = [];

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $absolutePath = $fileInfo->getPathname();
            $relativePath = str_replace('\\', '/', ltrim(str_replace(rtrim($basePath, '/'), '', $absolutePath), '/'));

            if ($this->shouldSkip($relativePath, $ignorePathPatterns)) {
                continue;
            }

            if ($relativeSubsetLookup !== null && ! isset($relativeSubsetLookup[trim($relativePath, '/')])) {
                continue;
            }

            $contents = @file_get_contents($absolutePath);

            if (! is_string($contents)) {
                continue;
            }

            $projectFiles[] = new ProjectFile($absolutePath, $relativePath, $contents);
        }

        return $projectFiles;
    }

    /**
     * @param  array<int, string>  $ignorePathPatterns
     */
    private function shouldSkip(string $relativePath, array $ignorePathPatterns): bool
    {
        $defaultSkippedPrefixes = [
            'vendor/',
            'node_modules/',
            'storage/',
            'bootstrap/cache/',
        ];

        foreach ($defaultSkippedPrefixes as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return true;
            }
        }

        foreach ($ignorePathPatterns as $ignorePathPattern) {
            if (fnmatch($ignorePathPattern, $relativePath)) {
                return true;
            }
        }

        return false;
    }
}
