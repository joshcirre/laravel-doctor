<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class DeepNestingRule implements Rule
{
    public function __construct(private readonly int $depthThreshold = 4)
    {
    }

    public function id(): string
    {
        return 'laravel/deep-nesting';
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
            $depth = 0;
            $maxDepth = 0;
            $line = 1;
            $lineAtMaxDepth = 1;

            $length = strlen($projectFile->contents);
            for ($index = 0; $index < $length; $index++) {
                $character = $projectFile->contents[$index];

                if ($character === "\n") {
                    $line++;
                    continue;
                }

                if ($character === '{') {
                    $depth++;

                    if ($depth > $maxDepth) {
                        $maxDepth = $depth;
                        $lineAtMaxDepth = $line;
                    }

                    continue;
                }

                if ($character === '}') {
                    $depth = max(0, $depth - 1);
                }
            }

            if ($maxDepth <= $this->depthThreshold) {
                continue;
            }

            $diagnostics[] = new Diagnostic(
                rule: $this->id(),
                category: $this->category(),
                severity: $this->defaultSeverity(),
                message: sprintf('Nested block depth reaches %d (threshold: %d).', $maxDepth, $this->depthThreshold),
                help: 'Flatten control flow with guard clauses and extracted methods for readability.',
                file: $projectFile->relativePath,
                line: $lineAtMaxDepth,
            );
        }

        return $diagnostics;
    }
}
