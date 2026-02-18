<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers;

use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Scanner\ProjectFile;
use Josh\LaravelDoctor\Scanner\ScanResult;

class FixRunner
{
    /**
     * @param  array<string, \Josh\LaravelDoctor\Fixers\Contracts\Fixer>  $fixersByRule
     */
    public function run(ScanResult $scanResult, string $basePath, array $fixersByRule, bool $dryRun = false): FixResult
    {
        $diagnosticsByFile = [];
        foreach ($scanResult->diagnostics as $diagnostic) {
            $diagnosticsByFile[$diagnostic->file][] = $diagnostic;
        }

        $appliedFixes = 0;
        $fixedFiles = [];
        $appliedRules = [];
        $unfixableRules = [];

        foreach ($diagnosticsByFile as $relativePath => $diagnostics) {
            $absolutePath = rtrim($basePath, '/').'/'.$relativePath;
            $originalContents = @file_get_contents($absolutePath);

            if (! is_string($originalContents)) {
                continue;
            }

            $projectFile = new ProjectFile($absolutePath, $relativePath, $originalContents);
            $updatedContents = $originalContents;
            $fileChanged = false;

            $seenRuleIds = [];

            foreach ($diagnostics as $diagnostic) {
                if (in_array($diagnostic->rule, $seenRuleIds, true)) {
                    continue;
                }

                $seenRuleIds[] = $diagnostic->rule;

                $fixer = $fixersByRule[$diagnostic->rule] ?? null;

                if ($fixer === null) {
                    $unfixableRules[] = $diagnostic->rule;
                    continue;
                }

                $candidateContents = $fixer->apply(new ProjectFile($absolutePath, $relativePath, $updatedContents));

                if (! is_string($candidateContents) || $candidateContents === $updatedContents) {
                    continue;
                }

                $updatedContents = $candidateContents;
                $fileChanged = true;
                $appliedFixes++;
                $appliedRules[] = $diagnostic->rule;
            }

            if (! $fileChanged) {
                continue;
            }

            if (! $dryRun) {
                file_put_contents($projectFile->absolutePath, $updatedContents);
            }

            $fixedFiles[] = $relativePath;
        }

        return new FixResult(
            appliedFixes: $appliedFixes,
            fixedFiles: array_values(array_unique($fixedFiles)),
            appliedRules: array_values(array_unique($appliedRules)),
            unfixableRules: array_values(array_unique($unfixableRules)),
        );
    }
}
