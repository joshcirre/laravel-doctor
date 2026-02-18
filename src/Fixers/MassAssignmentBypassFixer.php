<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers;

use Josh\LaravelDoctor\Fixers\Contracts\Fixer;
use Josh\LaravelDoctor\Scanner\ProjectFile;

class MassAssignmentBypassFixer implements Fixer
{
    public function rule(): string
    {
        return 'laravel/no-mass-assignment-bypass';
    }

    public function description(): string
    {
        return 'Replace $request->all() payload writes with $request->validated().';
    }

    public function apply(ProjectFile $projectFile): ?string
    {
        $updatedContents = $projectFile->contents;

        $patterns = [
            '/(->fill\s*\(\s*)\$request->all\s*\(\s*\)(\s*\))/' => '$1$request->validated()$2',
            '/(::create\s*\(\s*)\$request->all\s*\(\s*\)(\s*\))/' => '$1$request->validated()$2',
            '/(->update\s*\(\s*)\$request->all\s*\(\s*\)(\s*\))/' => '$1$request->validated()$2',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $candidate = preg_replace($pattern, $replacement, $updatedContents);

            if (is_string($candidate)) {
                $updatedContents = $candidate;
            }
        }

        if ($updatedContents === $projectFile->contents) {
            return null;
        }

        return $updatedContents;
    }
}
