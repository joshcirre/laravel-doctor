<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scoring;

use Josh\LaravelDoctor\Diagnostics\Diagnostic;

class ScoreCalculator
{
    private const MAX_SCORE = 100;
    private const REPEAT_DECAY_FACTOR = 0.55;
    private const RULE_PENALTY_CAP = 18.0;
    private const TOTAL_PENALTY_CAP = 95.0;

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

        $penalty = 0.0;

        foreach ($rulePenaltyBuckets as $bucket) {
            $bucketPenalty = 0.0;

            foreach ($bucket as $index => $basePenalty) {
                $bucketPenalty += $basePenalty / (1 + ($index * self::REPEAT_DECAY_FACTOR));
            }

            $penalty += min($bucketPenalty, self::RULE_PENALTY_CAP);
        }

        $penalty = min($penalty, self::TOTAL_PENALTY_CAP);
        $score = (int) max(0, round(self::MAX_SCORE - $penalty));

        return new ScoreResult(
            score: $score,
            label: $this->labelFor($score),
            penalty: (int) round($penalty),
        );
    }

    private function labelFor(int $score): string
    {
        if ($score >= 80) {
            return 'Great';
        }

        if ($score >= 55) {
            return 'Needs work';
        }

        return 'Critical';
    }
}
