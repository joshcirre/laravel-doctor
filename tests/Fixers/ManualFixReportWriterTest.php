<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Tests\Fixers;

use Josh\LaravelDoctor\Diagnostics\Category;
use Josh\LaravelDoctor\Diagnostics\Diagnostic;
use Josh\LaravelDoctor\Diagnostics\Severity;
use Josh\LaravelDoctor\Fixers\ManualFixReportWriter;
use Josh\LaravelDoctor\Scanner\ScanResult;
use PHPUnit\Framework\TestCase;

class ManualFixReportWriterTest extends TestCase
{
    public function test_writes_manual_fix_markdown_with_prompt_and_checklist(): void
    {
        $basePath = sys_get_temp_dir().'/laravel-doctor-report-'.uniqid();
        mkdir($basePath, 0755, true);

        $scanResult = new ScanResult(
            diagnostics: [
                new Diagnostic(
                    rule: 'laravel/controller-missing-validation',
                    category: Category::Correctness,
                    severity: Severity::Warning,
                    message: 'validation missing',
                    help: 'add validation',
                    file: 'app/Http/Controllers/UserController.php',
                    line: 22,
                ),
            ],
            scannedFileCount: 1,
            scannedFiles: ['app/Http/Controllers/UserController.php'],
        );

        $path = (new ManualFixReportWriter())->write($basePath, $scanResult, ['laravel/controller-missing-validation']);
        $contents = file_get_contents($path);

        self::assertIsString($contents);
        self::assertStringContainsString('Prompt To Paste Into Your Agent', $contents);
        self::assertStringContainsString('laravel/controller-missing-validation', $contents);
        self::assertStringContainsString('app/Http/Controllers/UserController.php:22', $contents);
    }
}
