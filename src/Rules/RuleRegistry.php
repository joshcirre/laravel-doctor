<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Rules;

use Josh\LaravelDoctor\Contracts\Rule;

class RuleRegistry
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<int, Rule>
     */
    public function defaults(array $config): array
    {
        $controllerLineThreshold = (int) ($config['thresholds']['controller_lines'] ?? 300);
        $nestingDepthThreshold = (int) ($config['thresholds']['nesting_depth'] ?? 4);

        return [
            new DebugStatementRule(),
            new EnvOutsideConfigRule(),
            new MassAssignmentBypassRule(),
            new MissingValidationRule(),
            new BroadCatchRule(),
            new LikelyNPlusOneRule(),
            new FatControllerRule($controllerLineThreshold),
            new DeepNestingRule($nestingDepthThreshold),
        ];
    }
}
