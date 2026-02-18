<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scanner;

use Josh\LaravelDoctor\Contracts\Rule;

class DoctorScanner
{
    /**
     * @param  array<int, Rule>  $rules
     */
    public function __construct(
        private readonly FileCollector $fileCollector,
        private readonly array $rules,
    ) {
    }

    /**
     * @param  array<int, string>|null  $fileSubset
     * @param  array<int, string>  $ignoredRuleIds
     * @param  array<int, string>  $ignorePathPatterns
     */
    public function scan(
        string $basePath,
        ?array $fileSubset,
        array $ignoredRuleIds,
        array $ignorePathPatterns,
    ): ScanResult {
        $files = $this->fileCollector->collect($basePath, $fileSubset, $ignorePathPatterns);

        $projectContext = new ProjectContext($basePath, $files, $ignoredRuleIds);
        $diagnostics = [];

        foreach ($this->rules as $rule) {
            if (in_array($rule->id(), $ignoredRuleIds, true)) {
                continue;
            }

            $diagnostics = [...$diagnostics, ...$rule->analyze($projectContext)];
        }

        usort($diagnostics, static function ($left, $right): int {
            $severityOrder = ['error' => 0, 'warning' => 1];

            $leftSeverity = $severityOrder[$left->severity->value] ?? 2;
            $rightSeverity = $severityOrder[$right->severity->value] ?? 2;

            if ($leftSeverity !== $rightSeverity) {
                return $leftSeverity <=> $rightSeverity;
            }

            return [$left->rule, $left->file, $left->line] <=> [$right->rule, $right->file, $right->line];
        });

        return new ScanResult($diagnostics, count($files));
    }
}
