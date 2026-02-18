<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules\Concerns;

trait FindsLineNumber
{
    protected function findLineNumber(string $contents, string $needle): int
    {
        $position = strpos($contents, $needle);

        if ($position === false) {
            return 0;
        }

        return substr_count(substr($contents, 0, $position), "\n") + 1;
    }
}
