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

class DoctorCommand extends Command
{
    protected $signature = 'doctor
        {--verbose : Show detailed file-level diagnostics}
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

        $diffOption = $this->option('diff');
        $diffBase = is_string($diffOption) && $diffOption !== '' ? $diffOption : null;
        $shouldUseDiff = $this->input->hasParameterOption('--diff') || $diffBase !== null;
        $scannedFilesSubset = $shouldUseDiff
            ? $gitDiffResolver->resolveChangedPhpFiles(base_path(), $diffBase)
            : null;

        if ($shouldUseDiff && $scannedFilesSubset === null && ! $this->option('score')) {
            $this->warn('Could not resolve diff base branch, falling back to full scan.');
        }

        $scanner = new DoctorScanner(
            fileCollector: $fileCollector,
            rules: $ruleRegistry->defaults($config),
        );

        $scanResult = $scanner->scan(
            basePath: base_path(),
            fileSubset: $scannedFilesSubset,
            ignoredRuleIds: $config['ignore']['rules'] ?? [],
            ignorePathPatterns: $config['ignore']['paths'] ?? [],
        );

        $scoreResult = $scoreCalculator->calculate($scanResult->diagnostics);

        if ($this->option('score')) {
            $this->line((string) $scoreResult->score);
            return $this->exitCodeForThreshold($scoreResult->score);
        }

        $format = strtolower((string) $this->option('format'));
        $formatter = match ($format) {
            'json' => new JsonOutputFormatter(),
            default => new TableOutputFormatter(),
        };

        $formatter->render(
            command: $this,
            scanResult: $scanResult,
            scoreResult: $scoreResult,
            verbose: (bool) $this->option('verbose'),
        );

        return $this->exitCodeForThreshold($scoreResult->score);
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
