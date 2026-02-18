<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;
use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Scanner\ProjectContext;

class LikelyNPlusOneRule implements Rule
{
    public function id(): string
    {
        return 'laravel/likely-n-plus-one';
    }

    public function category(): Category
    {
        return Category::Performance;
    }

    public function defaultSeverity(): Severity
    {
        return Severity::Warning;
    }

    public function analyze(ProjectContext $projectContext): array
    {
        $diagnostics = [];

        $loopPattern = '/foreach\s*\([^\)]*\)\s*\{(?P<body>[\s\S]{1,1200})\}/m';

        foreach ($projectContext->phpFiles() as $projectFile) {
            if (preg_match_all($loopPattern, $projectFile->contents, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }

            foreach ($matches['body'] ?? [] as $bodyMatch) {
                $body = $bodyMatch[0] ?? '';
                $offset = (int) ($bodyMatch[1] ?? 0);

                if (! preg_match('/->\w+\s*(->|\()/', $body)) {
                    continue;
                }

                if (preg_match('/with\(|load\(|loadMissing\(/', $projectFile->contents) === 1) {
                    continue;
                }

                $line = substr_count(substr($projectFile->contents, 0, $offset), "\n") + 1;

                $diagnostics[] = new Diagnostic(
                    rule: $this->id(),
                    category: $this->category(),
                    severity: $this->defaultSeverity(),
                    message: 'Likely N+1 query pattern in loop body.',
                    help: 'Consider eager loading relationships with with()/load() before looping.',
                    file: $projectFile->relativePath,
                    line: $line,
                );
            }
        }

        return $diagnostics;
    }
}
