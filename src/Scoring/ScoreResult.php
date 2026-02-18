<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scoring;

class ScoreResult
{
    public function __construct(
        public readonly int $score,
        public readonly string $label,
        public readonly int $penalty,
    ) {
    }
}
