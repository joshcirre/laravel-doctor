<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers;

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
        $fileDiffs = [];

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
            $fileDiffs[$relativePath] = $this->buildDiffPreview($relativePath, $originalContents, $updatedContents);
        }

        return new FixResult(
            appliedFixes: $appliedFixes,
            fixedFiles: array_values(array_unique($fixedFiles)),
            appliedRules: array_values(array_unique($appliedRules)),
            unfixableRules: array_values(array_unique($unfixableRules)),
            fileDiffs: $fileDiffs,
        );
    }

    private function buildDiffPreview(string $relativePath, string $originalContents, string $updatedContents): string
    {
        $originalLines = preg_split('/\R/', $originalContents) ?: [];
        $updatedLines = preg_split('/\R/', $updatedContents) ?: [];

        $originalCount = count($originalLines);
        $updatedCount = count($updatedLines);
        $maxLength = max($originalCount, $updatedCount);

        $firstDifference = null;
        $lastOriginalDifference = 0;
        $lastUpdatedDifference = 0;

        for ($index = 0; $index < $maxLength; $index++) {
            $left = $originalLines[$index] ?? null;
            $right = $updatedLines[$index] ?? null;

            if ($left === $right) {
                continue;
            }

            if ($firstDifference === null) {
                $firstDifference = $index;
            }

            $lastOriginalDifference = min($index, $originalCount - 1);
            $lastUpdatedDifference = min($index, $updatedCount - 1);
        }

        if ($firstDifference === null) {
            return '';
        }

        $contextSize = 2;
        $start = max(0, $firstDifference - $contextSize);
        $oldEnd = min($originalCount - 1, $lastOriginalDifference + $contextSize);
        $newEnd = min($updatedCount - 1, $lastUpdatedDifference + $contextSize);

        $diffLines = [
            sprintf('--- a/%s', $relativePath),
            sprintf('+++ b/%s', $relativePath),
            sprintf('@@ -%d,%d +%d,%d @@', $start + 1, ($oldEnd - $start) + 1, $start + 1, ($newEnd - $start) + 1),
        ];

        for ($lineIndex = $start; $lineIndex <= max($oldEnd, $newEnd); $lineIndex++) {
            $oldLine = $originalLines[$lineIndex] ?? null;
            $newLine = $updatedLines[$lineIndex] ?? null;

            if ($oldLine === $newLine) {
                if ($oldLine !== null) {
                    $diffLines[] = ' '.$oldLine;
                }

                continue;
            }

            if ($oldLine !== null) {
                $diffLines[] = '-'.$oldLine;
            }

            if ($newLine !== null) {
                $diffLines[] = '+'.$newLine;
            }
        }

        return implode("\n", $diffLines)."\n";
    }
}
