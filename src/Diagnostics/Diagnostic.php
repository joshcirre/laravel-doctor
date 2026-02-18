<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor\Diagnostics;

class Diagnostic
{
    public function __construct(
        public readonly string $rule,
        public readonly Category $category,
        public readonly Severity $severity,
        public readonly string $message,
        public readonly string $help,
        public readonly string $file,
        public readonly int $line = 0,
        public readonly int $column = 0,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule' => $this->rule,
            'category' => $this->category->value,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'help' => $this->help,
            'file' => $this->file,
            'line' => $this->line,
            'column' => $this->column,
        ];
    }
}
