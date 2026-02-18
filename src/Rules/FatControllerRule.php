<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class FatControllerRule implements Rule
{
    public function __construct(private readonly int $lineThreshold = 300)
    {
    }

    public function id(): string
    {
        return 'laravel/fat-controller';
    }

    public function category(): Category
    {
        return Category::Architecture;
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Warning;
    }

    public function analyze(ProjectContext $projectContext): array
    {
        $diagnostics = [];

        foreach ($projectContext->phpFiles() as $projectFile) {
            if (! str_starts_with($projectFile->relativePath, 'app/Http/Controllers/')) {
                continue;
            }

            if (! str_ends_with($projectFile->relativePath, 'Controller.php')) {
                continue;
            }

            $lineCount = substr_count($projectFile->contents, "\n") + 1;

            if ($lineCount <= $this->lineThreshold) {
                continue;
            }

            $diagnostics[] = new Diagnostic(
                rule: $this->id(),
                category: $this->category(),
                severity: $this->defaultSeverity(),
                message: sprintf('Controller has %d lines (threshold: %d).', $lineCount, $this->lineThreshold),
                help: 'Extract business logic into services/actions and keep controllers focused on HTTP orchestration.',
                file: $projectFile->relativePath,
                line: 1,
            );
        }

        return $diagnostics;
    }
}
