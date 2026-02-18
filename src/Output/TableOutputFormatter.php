<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Output;

use Illuminate\Console\Command;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Scoring\ScoreResult;
use Josh\LaravelDoctor\Scanner\ScanResult;

class TableOutputFormatter implements OutputFormatter
{
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

        $command->line(sprintf(
            'Found %d errors and %d warnings across %d scanned files.',
            $errorCount,
            $warningCount,
            $scanResult->scannedFileCount,
        ));
        $command->line(sprintf('Laravel Doctor score: <info>%d/100</info> (%s)', $scoreResult->score, $scoreResult->label));
    }
}
