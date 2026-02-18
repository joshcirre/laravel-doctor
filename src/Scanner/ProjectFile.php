<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Scanner;

class ProjectFile
{
    public function __construct(
        public readonly string $absolutePath,
        public readonly string $relativePath,
        public readonly string $contents,
    ) {
    }
}
