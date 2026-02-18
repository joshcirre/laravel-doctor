<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers;

class FixResult
{
    /**
     * @param  array<int, string>  $fixedFiles
     * @param  array<int, string>  $appliedRules
     * @param  array<int, string>  $unfixableRules
     */
    public function __construct(
        public readonly int $appliedFixes,
        public readonly array $fixedFiles,
        public readonly array $appliedRules,
        public readonly array $unfixableRules,
    ) {
    }
}
