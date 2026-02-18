<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scanner;

class ProjectContext
{
    /**
     * @param  array<int, ProjectFile>  $files
     * @param  array<int, string>  $ignoredRuleIds
     */
    public function __construct(
        public readonly string $basePath,
        public readonly array $files,
        public readonly array $ignoredRuleIds,
    ) {
    }

    /**
     * @return array<int, ProjectFile>
     */
    public function phpFiles(): array
    {
        return $this->files;
    }
}
