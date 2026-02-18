<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Tests\Fixers;

use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Fixers\DebugStatementFixer;
use Josh\LaravelDoctor\Fixers\FixRunner;
use Josh\LaravelDoctor\Scanner\ScanResult;
use PHPUnit\Framework\TestCase;

class FixRunnerTest extends TestCase
{
    public function test_collects_diff_preview_for_applied_fix(): void
    {
        $basePath = sys_get_temp_dir().'/laravel-doctor-test-'.uniqid();
        mkdir($basePath.'/app', 0755, true);

        $filePath = $basePath.'/app/Test.php';
        file_put_contents($filePath, "<?php\n\nfoo();\ndd(\$value);\nbar();\n");

        $scanResult = new ScanResult(
            diagnostics: [
                new Diagnostic(
                    rule: 'laravel/no-debug-statements',
                    category: Category::Security,
                    severity: Severity::Warning,
                    message: 'debug',
                    help: 'remove',
                    file: 'app/Test.php',
                    line: 4,
                ),
            ],
            scannedFileCount: 1,
            scannedFiles: ['app/Test.php'],
        );

        $fixResult = (new FixRunner())->run(
            scanResult: $scanResult,
            basePath: $basePath,
            fixersByRule: ['laravel/no-debug-statements' => new DebugStatementFixer()],
            dryRun: true,
        );

        self::assertSame(1, $fixResult->appliedFixes);
        self::assertArrayHasKey('app/Test.php', $fixResult->fileDiffs);
        self::assertStringContainsString('--- a/app/Test.php', $fixResult->fileDiffs['app/Test.php']);
    }
}
