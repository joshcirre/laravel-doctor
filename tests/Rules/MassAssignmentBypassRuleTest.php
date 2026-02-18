<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Tests\Rules;

use Josh\LaravelDoctor\Rules\MassAssignmentBypassRule;
use Josh\LaravelDoctor\Scanner\ProjectContext;
use Josh\LaravelDoctor\Scanner\ProjectFile;
use PHPUnit\Framework\TestCase;

class MassAssignmentBypassRuleTest extends TestCase
{
    public function test_flags_request_all_create_pattern(): void
    {
        $rule = new MassAssignmentBypassRule();
        $context = new ProjectContext(
            basePath: '/fake',
            files: [
                new ProjectFile(
                    '/fake/app/Http/Controllers/UserController.php',
                    'app/Http/Controllers/UserController.php',
                    "<?php\nUser::create(
                        \$request->all()
                    );"
                ),
            ],
            ignoredRuleIds: [],
        );

        $diagnostics = $rule->analyze($context);

        self::assertCount(1, $diagnostics);
        self::assertSame('laravel/no-mass-assignment-bypass', $diagnostics[0]->rule);
    }
}
