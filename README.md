# Laravel Doctor

Artisan-first Laravel codebase health scanner inspired by React Doctor.

`laravel-doctor` reports actionable diagnostics across security, correctness, performance, architecture, and maintainability, then computes a local deterministic 0-100 score.

## Install

```bash
composer require --dev joshcirre/laravel-doctor
```

Publish config:

```bash
php artisan vendor:publish --tag=laravel-doctor-config
```

## Usage

Run a full scan:

```bash
php artisan doctor
```

Common options:

```bash
php artisan doctor -v
php artisan doctor --score
php artisan doctor --format=json
php artisan doctor --diff
php artisan doctor --diff=main
php artisan doctor --min-score=75
```

Progress and banner output use Laravel's console UI components by default. Disable progress indicators with:

```bash
php artisan doctor --no-progress
```

## Auto-Fix

Apply safe built-in automated fixes:

```bash
php artisan doctor:fix
```

Preview changes only:

```bash
php artisan doctor:fix --dry-run
```

Apply only for changed files:

```bash
php artisan doctor:fix --diff
```

Run an optional Rector pass after built-in fixers (when `vendor/bin/rector` exists):

```bash
php artisan doctor:fix --with-rector
```

## Current Rule Set (MVP)

- `laravel/no-debug-statements`
- `laravel/no-env-outside-config`
- `laravel/no-mass-assignment-bypass`
- `laravel/controller-missing-validation`
- `laravel/no-broad-catch`
- `laravel/likely-n-plus-one`
- `laravel/fat-controller`
- `laravel/deep-nesting`

## Scoring

- Starts at `100`
- Applies weighted penalties by severity and category
- Caps per-rule penalty to avoid one repeated issue collapsing score
- Labels:
  - `80+`: Great
  - `55-79`: Needs work
  - `0-54`: Critical

## CI Example

```bash
php artisan doctor --format=json --min-score=75
```

The command exits non-zero when score is below `--min-score`.

## Skill

This repository includes an Agent Skill at `skills/laravel-doctor/SKILL.md` for AI agents to run Laravel Doctor and remediate findings in priority order.
