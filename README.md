# Laravel Doctor

Artisan-first Laravel codebase health scanner inspired by React Doctor.

`laravel-doctor` reports actionable diagnostics across security, correctness, performance, architecture, and maintainability, then computes a local deterministic 0-100 score.

## Install

```bash
composer require --dev josh/laravel-doctor
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
php artisan doctor --verbose
php artisan doctor --score
php artisan doctor --format=json
php artisan doctor --diff
php artisan doctor --diff=main
php artisan doctor --min-score=75
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
  - `75+`: Great
  - `50-74`: Needs work
  - `0-49`: Critical

## CI Example

```bash
php artisan doctor --format=json --min-score=75
```

The command exits non-zero when score is below `--min-score`.

## Skill

This repository includes an Agent Skill at `skills/laravel-doctor/SKILL.md` for AI agents to run Laravel Doctor and remediate findings in priority order.
