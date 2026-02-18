<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scoring;

use Josh\LaravelDoctor\Diagnostics\Diagnostic;

class ScoreCalculator
{
    private const MAX_SCORE = 100;

    /**
     * @param  array<int, Diagnostic>  $diagnostics
     */
    public function calculate(array $diagnostics): ScoreResult
    {
        $rulePenaltyBuckets = [];

        foreach ($diagnostics as $diagnostic) {
            $basePenalty = $diagnostic->severity->weight() * $diagnostic->category->multiplier();
            $rulePenaltyBuckets[$diagnostic->rule][] = $basePenalty;
        }

        $penalty = 0;

        foreach ($rulePenaltyBuckets as $bucket) {
            $cap = 25;
            $bucketPenalty = (int) round(array_sum($bucket));
            $penalty += min($bucketPenalty, $cap);
        }

        $score = max(0, self::MAX_SCORE - $penalty);

        return new ScoreResult(
            score: $score,
            label: $this->labelFor($score),
            penalty: $penalty,
        );
    }

    private function labelFor(int $score): string
    {
        if ($score >= 75) {
            return 'Great';
        }

        if ($score >= 50) {
            return 'Needs work';
        }

        return 'Critical';
    }
}
