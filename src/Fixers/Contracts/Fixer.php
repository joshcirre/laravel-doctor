<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers\Contracts;

use Josh\LaravelDoctor\Scanner\ProjectFile;

interface Fixer
{
    public function rule(): string;

    public function description(): string;

    public function apply(ProjectFile $projectFile): ?string;
}
