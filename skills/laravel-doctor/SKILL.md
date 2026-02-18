---
name: laravel-doctor
description: Diagnose Laravel codebase health and fix issues by severity using the artisan doctor command.
---

# Laravel Doctor Skill

Use this skill when auditing a Laravel codebase for quality, security, performance, correctness, and maintainability.

## Workflow

1. Run Laravel Doctor at project root:

```bash
php artisan doctor -v
```

2. Fix issues in this order:
- Errors before warnings
- Security and correctness before architecture/maintainability
- High-count rules before low-count rules

3. Re-run the scan and verify the score improved:

```bash
php artisan doctor --score
```

## PR / Branch Mode

For focused branch checks, scan only changed files:

```bash
php artisan doctor --diff
```

If needed, choose a base branch explicitly:

```bash
php artisan doctor --diff=main
```

## CI Gate

Fail when project health drops below threshold:

```bash
php artisan doctor --min-score=75
```

## Notes

- Use `--format=json` when another tool needs machine-readable output.
- Keep suppressions minimal; prefer fixing root causes.
