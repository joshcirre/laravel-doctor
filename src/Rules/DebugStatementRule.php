<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Rules\Concerns\FindsLineNumber;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class DebugStatementRule implements Rule
{
    use FindsLineNumber;

    public function id(): string
    {
        return 'laravel/no-debug-statements';
    }

    public function category(): Category
    {
        return Category::Security;
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Warning;
    }

    public function analyze(ProjectContext $projectContext): array
    {
        $diagnostics = [];

        foreach ($projectContext->phpFiles() as $projectFile) {
            foreach (['dd(', 'dump(', 'var_dump(', 'ray('] as $debugCall) {
                if (! str_contains($projectFile->contents, $debugCall)) {
                    continue;
                }

                $diagnostics[] = new Diagnostic(
                    rule: $this->id(),
                    category: $this->category(),
                    severity: $this->defaultSeverity(),
                    message: sprintf('Found debug call `%s` in application code.', rtrim($debugCall, '(')),
                    help: 'Remove debug helpers from committed app code. Prefer structured logging where needed.',
                    file: $projectFile->relativePath,
                    line: $this->findLineNumber($projectFile->contents, $debugCall),
                );
            }
        }

        return $diagnostics;
    }
}
