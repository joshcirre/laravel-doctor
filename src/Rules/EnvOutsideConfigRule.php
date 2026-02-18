<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Rules\Concerns\FindsLineNumber;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class EnvOutsideConfigRule implements Rule
{
    use FindsLineNumber;

    public function id(): string
    {
        return 'laravel/no-env-outside-config';
    }

    public function category(): Category
    {
        return Category::Correctness;
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Warning;
    }

    public function analyze(ProjectContext $projectContext): array
    {
        $diagnostics = [];

        foreach ($projectContext->phpFiles() as $projectFile) {
            if (! str_contains($projectFile->contents, 'env(')) {
                continue;
            }

            if (str_starts_with($projectFile->relativePath, 'config/')) {
                continue;
            }

            $diagnostics[] = new Diagnostic(
                rule: $this->id(),
                category: $this->category(),
                severity: $this->defaultSeverity(),
                message: 'Direct env() usage outside config files can break config caching.',
                help: 'Read values through config() in runtime code. Keep env() calls in config/*.php only.',
                file: $projectFile->relativePath,
                line: $this->findLineNumber($projectFile->contents, 'env('),
            );
        }

        return $diagnostics;
    }
}
