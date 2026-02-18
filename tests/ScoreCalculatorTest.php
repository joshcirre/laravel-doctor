<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Tests;

use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Scoring\ScoreCalculator;
use PHPUnit\Framework\TestCase;

class ScoreCalculatorTest extends TestCase
{
    public function test_calculates_expected_label_for_healthy_project(): void
    {
        $calculator = new ScoreCalculator();

        $result = $calculator->calculate([]);

        self::assertSame(100, $result->score);
        self::assertSame('Great', $result->label);
    }

    public function test_caps_penalty_per_rule(): void
    {
        $calculator = new ScoreCalculator();

        $diagnostics = [];

        for ($index = 0; $index < 20; $index++) {
            $diagnostics[] = new Diagnostic(
                rule: 'laravel/no-mass-assignment-bypass',
                category: Category::Security,
                severity: Severity::Error,
                message: 'Issue',
                help: 'Fix',
                file: 'app/Http/Controllers/UserController.php',
                line: $index + 1,
            );
        }

        $result = $calculator->calculate($diagnostics);

        self::assertSame(75, $result->score);
        self::assertSame('Great', $result->label);
    }
}
