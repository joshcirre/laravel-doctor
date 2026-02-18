<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Tests\Fixers;

use Josh\LaravelDoctor\Fixers\MassAssignmentBypassFixer;
use Josh\LaravelDoctor\Scanner\ProjectFile;
use PHPUnit\Framework\TestCase;

class MassAssignmentBypassFixerTest extends TestCase
{
    public function test_rewrites_request_all_to_validated_payloads(): void
    {
        $fixer = new MassAssignmentBypassFixer();
        $file = new ProjectFile(
            '/tmp/UserController.php',
            'app/Http/Controllers/UserController.php',
            "<?php\n\nUser::create(\$request->all());\n\$user->update(\$request->all());\n"
        );

        $updated = $fixer->apply($file);

        self::assertIsString($updated);
        self::assertStringContainsString('User::create($request->validated());', $updated);
        self::assertStringContainsString('$user->update($request->validated());', $updated);
    }
}
