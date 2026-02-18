<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class MissingValidationRule implements Rule
{
    public function id(): string
    {
        return 'laravel/controller-missing-validation';
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
            if (! str_starts_with($projectFile->relativePath, 'app/Http/Controllers/')) {
                continue;
            }

            if (! preg_match('/function\s+(store|update)\s*\(/', $projectFile->contents)) {
                continue;
            }

            if (preg_match('/validated\(|->validate\(|FormRequest/', $projectFile->contents) === 1) {
                continue;
            }

            $diagnostics[] = new Diagnostic(
                rule: $this->id(),
                category: $this->category(),
                severity: $this->defaultSeverity(),
                message: 'Controller write method appears to miss request validation.',
                help: 'Validate input via FormRequest or $request->validate() before writing models.',
                file: $projectFile->relativePath,
                line: 1,
            );
        }

        return $diagnostics;
    }
}
