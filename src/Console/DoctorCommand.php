<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Console;

use Illuminate\Console\Command;
use Josh\LaravelDoctor\Output\JsonOutputFormatter;
use Josh\LaravelDoctor\Output\TableOutputFormatter;
use Josh\LaravelDoctor\Rules\RuleRegistry;
use Josh\LaravelDoctor\Scanner\DoctorScanner;
use Josh\LaravelDoctor\Scanner\FileCollector;
use Josh\LaravelDoctor\Scanner\GitDiffResolver;
use Josh\LaravelDoctor\Scoring\ScoreCalculator;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class DoctorCommand extends Command
{
    protected $signature = 'doctor
        {--score : Output only numeric score}
        {--diff= : Scan only changed files. Optional base branch, e.g. --diff=main}
        {--format=table : Output format (table|json)}
        {--min-score= : Fail command when score is below this threshold}
        {--no-progress : Disable progress indicators}';

    protected $description = 'Diagnose Laravel codebase health with actionable checks.';

    public function handle(
        RuleRegistry $ruleRegistry,
        FileCollector $fileCollector,
        GitDiffResolver $gitDiffResolver,
        ScoreCalculator $scoreCalculator,
    ): int {
        /** @var array<string, mixed> $config */
        $config = config('laravel-doctor', []);

        $format = strtolower((string) $this->option('format'));
        $isScoreOnly = (bool) $this->option('score');
        $useStyledOutput = ! $isScoreOnly && $format === 'table';
        $showProgress = $useStyledOutput && ! (bool) $this->option('no-progress');

        if ($useStyledOutput) {
            $this->renderBanner();
            $this->newLine();
        }

        $rules = $ruleRegistry->defaults($config);

        if ($showProgress) {
            note(sprintf('Loaded %d diagnostic rules.', count($rules)));
        }

        $diffOption = $this->option('diff');
        $diffBase = is_string($diffOption) && $diffOption !== '' ? $diffOption : null;
        $shouldUseDiff = $this->input->hasParameterOption('--diff') || $diffBase !== null;
        $scannedFilesSubset = $shouldUseDiff
            ? $gitDiffResolver->resolveChangedPhpFiles(base_path(), $diffBase)
            : null;

        if ($showProgress) {
            $scanMode = $shouldUseDiff ? 'Diff scan mode enabled' : 'Full project scan enabled';
            note($scanMode);
        }

        if ($shouldUseDiff && $scannedFilesSubset === null && ! $this->option('score')) {
            warning('Could not resolve diff base branch, falling back to full scan.');
        }

        $scanner = new DoctorScanner(
            fileCollector: $fileCollector,
            rules: $rules,
        );

        $scanAction = fn () => $scanner->scan(
            basePath: base_path(),
            fileSubset: $scannedFilesSubset,
            ignoredRuleIds: $config['ignore']['rules'] ?? [],
            ignorePathPatterns: $config['ignore']['paths'] ?? [],
        );

        $scanResult = $showProgress
            ? spin($scanAction, 'Running diagnostics...')
            : $scanAction();

        if ($showProgress) {
            note(sprintf('Scanned %d PHP files.', $scanResult->scannedFileCount));

            if ($this->output->isVerbose()) {
                $this->line('<fg=cyan>Files scanned:</>');

                $filesToDisplay = $this->output->isVeryVerbose()
                    ? $scanResult->scannedFiles
                    : array_slice($scanResult->scannedFiles, 0, 60);

                foreach ($filesToDisplay as $relativePath) {
                    $this->line('  - '.$relativePath);
                }

                if (! $this->output->isVeryVerbose() && count($scanResult->scannedFiles) > count($filesToDisplay)) {
                    note(sprintf(
                        'Showing %d/%d files. Re-run with -vv to print every scanned file.',
                        count($filesToDisplay),
                        count($scanResult->scannedFiles),
                    ));
                }
            }

            $this->newLine();
        }

        $scoreResult = $scoreCalculator->calculate($scanResult->diagnostics);

        if ($isScoreOnly) {
            $this->line((string) $scoreResult->score);
            return $this->exitCodeForThreshold($scoreResult->score);
        }

        $formatter = match ($format) {
            'json' => new JsonOutputFormatter(),
            default => new TableOutputFormatter(),
        };

        $showDetailedDiagnostics = $this->output->isVerbose();

        $formatter->render(
            command: $this,
            scanResult: $scanResult,
            scoreResult: $scoreResult,
            verbose: $showDetailedDiagnostics,
        );

        return $this->exitCodeForThreshold($scoreResult->score);
    }

    private function renderBanner(): void
    {
        $bannerLines = [
            ' _                                _ ____              _             ',
            '| |    __ _ _ __ __ ___   _____| |  _ \  ___   ___| |_ ___  _ __ ',
            '| |   / _` | `__/ _` \ \ / / _ \ | | | |/ _ \ / __| __/ _ \| `__|',
            '| |__| (_| | | | (_| |\ V /  __/ | |_| | (_) | (__| || (_) | |   ',
            '|_____\__,_|_|  \__,_| \_/ \___|_|____/ \___/ \___|\__\___/|_|   ',
        ];

        foreach ($bannerLines as $line) {
            $this->line('<fg=red;options=bold>'.$line.'</>');
        }

        $this->line('<fg=yellow>Laravel Doctor</> <fg=gray>for Laravel and PHP code health</>');
    }

    private function exitCodeForThreshold(int $score): int
    {
        $minScore = $this->option('min-score');
        if ($minScore === null || $minScore === '') {
            return self::SUCCESS;
        }

        $threshold = (int) $minScore;

        if ($score < $threshold) {
            $this->line(sprintf('Score %d is below min-score %d.', $score, $threshold));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
