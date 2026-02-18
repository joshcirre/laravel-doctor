<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers;

use Josh\LaravelDoctor\Fixers\Contracts\Fixer;
use Josh\LaravelDoctor\Scanner\ProjectFile;

class DebugStatementFixer implements Fixer
{
    public function rule(): string
    {
        return 'laravel/no-debug-statements';
    }

    public function description(): string
    {
        return 'Remove direct debug helper lines (dd, dump, var_dump, ray).';
    }

    public function apply(ProjectFile $projectFile): ?string
    {
        $updatedContents = preg_replace('/^\s*(dd|dump|var_dump|ray)\s*\(.*\)\s*;\s*$/m', '', $projectFile->contents);

        if (! is_string($updatedContents)) {
            return null;
        }

        if ($updatedContents === $projectFile->contents) {
            return null;
        }

        $updatedContents = preg_replace("/\n{3,}/", "\n\n", $updatedContents);

        return is_string($updatedContents) ? $updatedContents : null;
    }
}
