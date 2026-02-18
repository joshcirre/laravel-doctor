<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scanner;

use Josh\LaravelDoctor\Diagnostics\Diagnostic;

class ScanResult
{
    /**
     * @param  array<int, Diagnostic>  $diagnostics
     */
    public function __construct(
        public readonly array $diagnostics,
        public readonly int $scannedFileCount,
    ) {
    }
}
