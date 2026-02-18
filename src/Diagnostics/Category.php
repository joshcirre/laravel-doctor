<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Diagnostics;

enum Category: string
{
    case Security = 'Security';
    case Correctness = 'Correctness';
    case Performance = 'Performance';
    case Architecture = 'Architecture';
    case Maintainability = 'Maintainability';

    public function multiplier(): float
    {
        return match ($this) {
            self::Security => 1.4,
            self::Correctness => 1.25,
            self::Performance => 1.0,
            self::Architecture => 0.8,
            self::Maintainability => 0.6,
        };
    }
}
