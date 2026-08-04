# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-07-17

### Added
- `OutputInterface` — pluggable output abstraction for the progress bar
- `ConsoleOutput` — default output handler (writes to STDOUT)
- `CallbackOutput` — delegates output to a user-provided callback
- `LoggerOutput` — routes output to a PSR-3 logger (`psr/log` suggested)
- `setOutput()` / `getOutput()` methods for runtime output handler changes
- `LoggerOutputTest` — 13 unit and integration tests covering all output handlers
- PHPStan static analysis at `level max` with `phpstan.neon.dist` config
- Composer scripts: `test`, `phpstan`, `check` (runs both sequentially)
- `.github/workflows/ci.yml` — CI workflow with matrix for PHP 8.4 / 8.5
- `.github/ISSUE_TEMPLATE/` — bug report and feature request templates
- `.github/PULL_REQUEST_TEMPLATE.md` — pull request template
- `.editorconfig` — coding style consistency
- `.gitattributes` — line ending normalization and export-ignore rules
- `LICENSE` — MIT license file
- CI badge in README
- Packagist version, downloads, and PHP version badges in README
- ConsoleOutput unit tests — `write()` and `writeln()` with edge cases
- Coverage configuration (phpunit.xml `<source>` filter)
- CHANGELOG.md with Keep a Changelog format
- `AGENTS.md` — instruction file for OpenCode sessions
- `README_ru.md` — Russian translation of README
- `CONTRIBUTING.md` — contribution guidelines

### Changed
- Constructor now accepts optional `OutputInterface` as third parameter
- `$limit ?:` fallback changed to `$limit > 0` — negative limits correctly fall back to `MIN_LIMIT`
- Fixed negative `$fullCount` in bar rendering (added `max(0, ...)`)
- `strlen` replaced with `mb_strlen` for consistency
- double quotes → single quotes in `sprintf` calls
- `property_exists(__CLASS__, ...)` → `property_exists(self::class, ...)`
- Comprehensive PHPDoc for all methods and properties
- Removed non-standard `@package` and `@author` PHPDoc tags
- Updated README with OutputInterface documentation, methods reference, and requirements
- PHPDoc `@var` removed from typed constants (types already declared in code)
- Default branch renamed from `master` to `main`
- CI workflow updated to trigger on `main` (with `workflow_dispatch`)
- `LoggerOutput::write()` now trims and skips empty strings (filters `\r` clearing writes)
- `progressBarSize` set hook throws `\InvalidArgumentException` on negative values

### Removed
- Removed unused `use InvalidArgumentException` import from `CallbackOutput`

## [0.0.7] - 2025-08-05

### Added
- Test suite with PHPUnit (initial tests for `ConsoleProgressBar`)

### Changed
- Code quality improvements across the codebase

## [0.0.6] - 2021-09-30

### Changed
- Updated `composer.json` for Composer 2.0 compatibility

## [0.0.5.1] - 2021-09-30

### Changed
- Updated `composer.json` for Composer 2.0 compatibility

## [0.0.5] - 2021-09-30

### Changed
- `composer.json` updates and dependency bumps

## [0.0.4] - 2021-01-30

### Changed
- Renamed example files for consistent naming:
  - `progress_bar_whit_percent.php` → `progress_bar_whith_percent.php`
  - `whitout_params.php` → `without_params.php`
  - `whitout_finish_report.php` → `without_finish_report.php`
  - `other_order_elements.php` → `other_order_by_elements.php`

## [0.0.3] - 2020-10-19

### Added
- Spinner self-counter animation

## [0.0.2] - 2020-10-12

### Added
- Output without parameters support

### Changed
- Updated license information
- Updated README documentation

## [0.0.1] - 2020-09-29

### Added
- Initial release
- Progress bar with configurable elements (spinner, bar, position, percent, time)
- Finish report after progress completion
- Customizable appearance (chars, size, separator, element order)
- 11 example scripts

[Unreleased]: https://github.com/kas-cor/console-progress-bar/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/kas-cor/console-progress-bar/compare/v0.0.7...v0.1.0
[0.0.7]: https://github.com/kas-cor/console-progress-bar/compare/0.0.6...v0.0.7
[0.0.6]: https://github.com/kas-cor/console-progress-bar/compare/0.0.5.1...0.0.6
[0.0.5.1]: https://github.com/kas-cor/console-progress-bar/compare/0.0.5...0.0.5.1
[0.0.5]: https://github.com/kas-cor/console-progress-bar/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/kas-cor/console-progress-bar/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/kas-cor/console-progress-bar/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/kas-cor/console-progress-bar/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/kas-cor/console-progress-bar/releases/tag/0.0.1
