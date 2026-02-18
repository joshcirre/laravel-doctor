<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Rules\Concerns\FindsLineNumber;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class BroadCatchRule implements Rule
{
    use FindsLineNumber;

    public function id(): string
    {
        return 'laravel/no-broad-catch';
    }

    public function category(): Category
    {
        return Category::Maintainability;
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Warning;
    }

    public function analyze(ProjectContext $projectContext): array
    {
        $diagnostics = [];

        foreach ($projectContext->phpFiles() as $projectFile) {
            foreach (['catch (\\Throwable', 'catch (\\Exception'] as $needle) {
                if (! str_contains($projectFile->contents, $needle)) {
                    continue;
                }

                $diagnostics[] = new Diagnostic(
                    rule: $this->id(),
                    category: $this->category(),
                    severity: $this->defaultSeverity(),
                    message: 'Broad exception catch found. This can hide real failures.',
                    help: 'Catch narrower exceptions and handle them explicitly. Re-throw unexpected failures.',
                    file: $projectFile->relativePath,
                    line: $this->findLineNumber($projectFile->contents, $needle),
                );
            }
        }

        return $diagnostics;
    }
}
