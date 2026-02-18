<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Output;

use Illuminate\Console\Command;
use Josh\LaravelDoctor\Scoring\ScoreResult;
use Josh\LaravelDoctor\Scanner\ScanResult;

class JsonOutputFormatter implements OutputFormatter
{
    public function render(Command $command, ScanResult $scanResult, ScoreResult $scoreResult, bool $verbose): void
    {
        $payload = [
            'score' => $scoreResult->score,
            'label' => $scoreResult->label,
            'penalty' => $scoreResult->penalty,
            'scanned_files' => $scanResult->scannedFileCount,
            'diagnostics' => array_map(static fn ($diagnostic): array => $diagnostic->toArray(), $scanResult->diagnostics),
        ];

        $command->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
