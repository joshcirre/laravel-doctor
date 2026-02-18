<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Fixers;

use Josh\LaravelDoctor\Fixers\Contracts\Fixer;

class FixerRegistry
{
    /**
     * @return array<string, Fixer>
     */
    public function all(): array
    {
        $fixers = [
            new DebugStatementFixer(),
            new MassAssignmentBypassFixer(),
        ];

        $indexedFixers = [];

        foreach ($fixers as $fixer) {
            $indexedFixers[$fixer->rule()] = $fixer;
        }

        return $indexedFixers;
    }
}
