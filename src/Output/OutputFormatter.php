<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Output;

use Illuminate\Console\Command;
use Josh\LaravelDoctor\Scoring\ScoreResult;
use Josh\LaravelDoctor\Scanner\ScanResult;

interface OutputFormatter
{
    public function render(Command $command, ScanResult $scanResult, ScoreResult $scoreResult, bool $verbose): void;
}
