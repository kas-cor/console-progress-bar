# AGENTS.md — Console Progress Bar

## Commands

```sh
composer check        # PHPStan (level max) → PHPUnit (order matters)
composer test         # PHPUnit only
composer phpstan      # static analysis only
composer cs-fix       # format code (not enforced by CI)
```

## Conventions

- `declare(strict_types=1)` in every file
- Native function calls prefixed with `\` (`\count()`, `\strlen()`, `\PHP_EOL`) — enforced by php-cs-fixer
- Single quotes preferred
- PHPDoc required on all public methods (`@param`, `@return`); omit redundant `@var` on typed properties/constants
- Conventional commits: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`

## Architecture

- `src/ConsoleProgressBar.php` — main class, public API entrypoint
- `src/Output/OutputInterface.php` — pluggable output contract
  - `ConsoleOutput` — default (echo to STDOUT)
  - `CallbackOutput` — user callback
  - `LoggerOutput` — PSR-3 adapter (`psr/log` suggested, not required)
- `tests/ConsoleProgressBarTest.php` + `tests/LoggerOutputTest.php` — all tests, single PHPUnit suite
- Config via constructor array OR direct property assignment (all public properties)
- Progress bar writes with `\r` carriage return (overwrites same line); finish report uses `\PHP_EOL`

## Testing

- `composer check` must pass before PR (PHPStan level max + PHPUnit)
- Project aims for 100% code coverage
- Coverage: `php -d pcov.enabled=1 ./vendor/bin/phpunit --coverage-text`
- No fixtures, no external services, no snapshot tests — pure unit tests with `CallbackOutput` for capturing output

## Gotchas

- `LoggerOutput::write()` trims input and skips empty strings — clearing writes (spaces + `\r`) are not logged
- `progressBarSize` set hook throws `\InvalidArgumentException` on negative values; zero is allowed (produces `[]`)
- Negative `$limit` is clamped to `MIN_LIMIT` (0.0000001)
- Default branch is `main` (not `master`)
