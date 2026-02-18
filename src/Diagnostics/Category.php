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
            self::Security => 1.7,
            self::Correctness => 1.5,
            self::Performance => 1.2,
            self::Architecture => 1.0,
            self::Maintainability => 0.8,
        };
    }
}
