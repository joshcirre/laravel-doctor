<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Console;

use Illuminate\Console\Command;
use Josh\LaravelDoctor\Fixers\FixRunner;
use Josh\LaravelDoctor\Fixers\FixerRegistry;
use Josh\LaravelDoctor\Fixers\ManualFixReportWriter;
use Josh\LaravelDoctor\Fixers\RectorRunner;
use Josh\LaravelDoctor\Rules\RuleRegistry;
use Josh\LaravelDoctor\Scanner\DoctorScanner;
use Josh\LaravelDoctor\Scanner\FileCollector;
use Josh\LaravelDoctor\Scanner\GitDiffResolver;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class DoctorFixCommand extends Command
{
    protected $signature = 'doctor:fix
        {--diff= : Fix only changed files. Optional base branch, e.g. --diff=main}
        {--dry-run : Show what would be changed without writing files}
        {--with-rector : Run Rector after built-in fixers when available}';

    protected $description = 'Apply safe automated fixes for Laravel Doctor diagnostics.';

    public function handle(
        RuleRegistry $ruleRegistry,
        FileCollector $fileCollector,
        GitDiffResolver $gitDiffResolver,
        FixerRegistry $fixerRegistry,
        FixRunner $fixRunner,
        ManualFixReportWriter $manualFixReportWriter,
        RectorRunner $rectorRunner,
    ): int {
        /** @var array<string, mixed> $config */
        $config = config('laravel-doctor', []);

        intro('Laravel Doctor Fix');

        $diffOption = $this->option('diff');
        $diffBase = is_string($diffOption) && $diffOption !== '' ? $diffOption : null;
        $shouldUseDiff = $this->input->hasParameterOption('--diff') || $diffBase !== null;
        $scannedFilesSubset = $shouldUseDiff
            ? $gitDiffResolver->resolveChangedPhpFiles(base_path(), $diffBase)
            : null;

        if ($shouldUseDiff && $scannedFilesSubset === null) {
            warning('Could not resolve diff base branch, falling back to full scan.');
        }

        $scanner = new DoctorScanner(
            fileCollector: $fileCollector,
            rules: $ruleRegistry->defaults($config),
        );

        $scanResult = spin(
            fn () => $scanner->scan(
                basePath: base_path(),
                fileSubset: $scannedFilesSubset,
                ignoredRuleIds: $config['ignore']['rules'] ?? [],
                ignorePathPatterns: $config['ignore']['paths'] ?? [],
            ),
            'Scanning for fixable diagnostics...'
        );

        if ($scanResult->diagnostics === []) {
            outro('No issues found. Nothing to fix.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $fixersByRule = $fixerRegistry->all();

        $fixResult = $fixRunner->run(
            scanResult: $scanResult,
            basePath: base_path(),
            fixersByRule: $fixersByRule,
            dryRun: $dryRun,
        );

        if ($dryRun) {
            note(sprintf('Dry run complete. %d potential fixes across %d files.', $fixResult->appliedFixes, count($fixResult->fixedFiles)));
        } else {
            note(sprintf('Applied %d fixes across %d files.', $fixResult->appliedFixes, count($fixResult->fixedFiles)));
        }

        if ($fixResult->fileDiffs !== []) {
            $this->newLine();
            note('Patch preview for programmatic fixes:');

            foreach ($fixResult->fileDiffs as $relativePath => $diffText) {
                if ($diffText === '') {
                    continue;
                }

                $this->line('<fg=cyan>'.$relativePath.'</>');
                $this->line($diffText);
            }
        }

        if ($fixResult->appliedRules !== []) {
            $this->line('Fixed rules:');
            foreach ($fixResult->appliedRules as $ruleId) {
                $this->line('  - '.$ruleId);
            }
        }

        if ($fixResult->unfixableRules !== []) {
            $this->newLine();
            warning('Some rules are currently diagnostic-only and require manual fixes:');
            foreach ($fixResult->unfixableRules as $ruleId) {
                $this->line('  - '.$ruleId);
            }

            $manualReportPath = $manualFixReportWriter->write(base_path(), $scanResult, $fixResult->unfixableRules);
            $relativeManualReportPath = ltrim(str_replace(base_path(), '', $manualReportPath), '/');

            $this->newLine();
            note(sprintf('Generated manual fix guide: %s', $relativeManualReportPath));
            note('Copy the "Prompt To Paste Into Your Agent" block from that file into your coding agent.');
        }

        if ((bool) $this->option('with-rector')) {
            $this->newLine();

            if (! $rectorRunner->isAvailable(base_path())) {
                warning('Rector not found at vendor/bin/rector. Skipping Rector pass.');
            } else {
                $rectorExitCode = spin(
                    fn () => $rectorRunner->run(base_path(), $dryRun, $scannedFilesSubset),
                    'Running Rector pass...'
                );

                if ($rectorExitCode !== 0) {
                    warning(sprintf('Rector finished with exit code %d.', $rectorExitCode));
                } else {
                    info('Rector pass complete.');
                }
            }
        }

        outro('Done. Run `php artisan doctor` again to verify score improvements.');

        return self::SUCCESS;
    }
}
