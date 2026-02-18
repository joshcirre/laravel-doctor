<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Output;

use Illuminate\Console\Command;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Scoring\ScoreResult;
use Josh\LaravelDoctor\Scanner\ScanResult;

class TableOutputFormatter implements OutputFormatter
{
    private const SCORE_BAR_WIDTH = 30;

    public function render(Command $command, ScanResult $scanResult, ScoreResult $scoreResult, bool $verbose): void
    {
        $diagnostics = $scanResult->diagnostics;

        if ($diagnostics === []) {
            $command->info('No issues found.');
            $command->newLine();
            $command->line(sprintf('Laravel Doctor score: <info>%d/100</info> (%s)', $scoreResult->score, $scoreResult->label));
            return;
        }

        $groupedByRule = [];
        foreach ($diagnostics as $diagnostic) {
            $groupedByRule[$diagnostic->rule][] = $diagnostic;
        }

        foreach ($groupedByRule as $ruleId => $ruleDiagnostics) {
            /** @var Diagnostic $firstDiagnostic */
            $firstDiagnostic = $ruleDiagnostics[0];
            $severityTag = $firstDiagnostic->severity->value === 'error' ? 'error' : 'comment';

            $command->line(sprintf('<%s>%s</%s> [%s] (%d)', $severityTag, $firstDiagnostic->message, $severityTag, $ruleId, count($ruleDiagnostics)));
            $command->line('  '.$firstDiagnostic->help);

            if ($verbose) {
                foreach ($ruleDiagnostics as $ruleDiagnostic) {
                    $lineLabel = $ruleDiagnostic->line > 0 ? ':'.$ruleDiagnostic->line : '';
                    $command->line('  - '.$ruleDiagnostic->file.$lineLabel);
                }
            }

            $command->newLine();
        }

        $errorCount = count(array_filter($diagnostics, static fn (Diagnostic $diagnostic): bool => $diagnostic->severity->value === 'error'));
        $warningCount = count($diagnostics) - $errorCount;
        $categoryCounts = [];

        foreach ($diagnostics as $diagnostic) {
            $categoryCounts[$diagnostic->category->value] = ($categoryCounts[$diagnostic->category->value] ?? 0) + 1;
        }

        arsort($categoryCounts);

        $command->line('<fg=cyan;options=bold>Diagnostic summary</>');
        foreach ($categoryCounts as $category => $count) {
            $command->line(sprintf('  - %s: %d', $category, $count));
        }

        $command->newLine();

        $command->line(sprintf(
            'Found %d errors and %d warnings across %d scanned files.',
            $errorCount,
            $warningCount,
            $scanResult->scannedFileCount,
        ));
        $command->line(sprintf('Laravel Doctor score: <info>%d/100</info> (%s)', $scoreResult->score, $scoreResult->label));
        $command->line('  '.$this->scoreBar($scoreResult->score));
    }

    private function scoreBar(int $score): string
    {
        $filledCount = (int) round(($score / 100) * self::SCORE_BAR_WIDTH);
        $emptyCount = self::SCORE_BAR_WIDTH - $filledCount;

        $filled = str_repeat('#', max(0, $filledCount));
        $empty = str_repeat('-', max(0, $emptyCount));
        $color = $score >= 80 ? 'green' : ($score >= 55 ? 'yellow' : 'red');

        return sprintf('<fg=%s>%s</><fg=gray>%s</>', $color, $filled, $empty);
    }
}
