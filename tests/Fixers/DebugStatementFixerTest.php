<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Tests\Fixers;

use Josh\LaravelDoctor\Fixers\DebugStatementFixer;
use Josh\LaravelDoctor\Scanner\ProjectFile;
use PHPUnit\Framework\TestCase;

class DebugStatementFixerTest extends TestCase
{
    public function test_removes_simple_debug_lines(): void
    {
        $fixer = new DebugStatementFixer();
        $file = new ProjectFile(
            '/tmp/Test.php',
            'app/Test.php',
            "<?php\n\nfoo();\ndd(\$user);\nbar();\n"
        );

        $updated = $fixer->apply($file);

        self::assertIsString($updated);
        self::assertStringNotContainsString('dd($user)', $updated);
        self::assertStringContainsString('foo();', $updated);
        self::assertStringContainsString('bar();', $updated);
    }
}
