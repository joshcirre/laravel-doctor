<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Diagnostics;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';

    public function weight(): int
    {
        return match ($this) {
            self::Error => 5,
            self::Warning => 2,
        };
    }
}
