<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Rules\Concerns\FindsLineNumber;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class MassAssignmentBypassRule implements Rule
{
    use FindsLineNumber;

    public function id(): string
    {
        return 'laravel/no-mass-assignment-bypass';
    }

    public function category(): Category
    {
        return Category::Security;
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Error;
    }

    public function analyze(ProjectContext $projectContext): array
    {
        $diagnostics = [];

        $patterns = [
            '/::unguard\s*\(/' => ['Avoid Model::unguard() in app code.', '::unguard('],
            '/forceFill\s*\(/' => ['Avoid forceFill() with untrusted data.', 'forceFill('],
            '/->fill\s*\(\s*\$request->all\s*\(/' => ['Avoid fill($request->all()). Use validated payloads.', '->fill('],
            '/::create\s*\(\s*\$request->all\s*\(/' => ['Avoid create($request->all()). Use validated payloads.', '::create('],
            '/->update\s*\(\s*\$request->all\s*\(/' => ['Avoid update($request->all()). Use validated payloads.', '->update('],
        ];

        foreach ($projectContext->phpFiles() as $projectFile) {
            foreach ($patterns as $pattern => [$message, $lineNeedle]) {
                if (preg_match($pattern, $projectFile->contents) !== 1) {
                    continue;
                }

                $diagnostics[] = new Diagnostic(
                    rule: $this->id(),
                    category: $this->category(),
                    severity: $this->defaultSeverity(),
                    message: $message,
                    help: 'Use $request->validated() from FormRequest or validator()->validated() before model writes.',
                    file: $projectFile->relativePath,
                    line: $this->findLineNumber($projectFile->contents, $lineNeedle),
                );
            }
        }

        return $diagnostics;
    }
}
