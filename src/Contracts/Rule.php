<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Contracts;

use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Scanner\ProjectContext;

interface Rule
{
    public function id(): string;

    public function category(): Category;

    public function defaultSeverity(): Severity;

    /**
     * @return array<int, Diagnostic>
     */
    public function analyze(ProjectContext $projectContext): array;
}
