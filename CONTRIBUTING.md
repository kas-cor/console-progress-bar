# Contributing

Contributions are welcome! Here's how you can help improve **Console Progress Bar**.

## Getting Started

1. Fork the repository
2. Clone your fork:
   ```bash
   git clone https://github.com/your-username/console-progress-bar.git
   cd console-progress-bar
   ```
3. Install dependencies:
   ```bash
   composer install
   ```

## Requirements

- PHP >= 8.4
- `ext-mbstring`

## Development Workflow

### Run Tests

```bash
composer test
```

### Run Static Analysis (PHPStan level max)

```bash
composer phpstan
```

### Run Both

```bash
composer check
```

This runs PHPStan first, then PHPUnit — both must pass before submitting a PR.

### Coverage Report

```bash
php -d pcov.enabled=1 ./vendor/bin/phpunit --coverage-text
```

> **Note:** Coverage requires the [pcov](https://github.com/krakjoe/pcov) PHP extension. Install it via `pecl install pcov` or enable it in your PHP configuration.

The project aims to maintain **100% code coverage**.

## Coding Standards

- Follow **PSR-12** coding style
- Use **strict types** (`declare(strict_types=1)`)
- All public methods must have **PHPDoc** with `@param` and `@return`
- Keep type declarations in code — redundant `@var` on typed properties/constants should be avoided
- Use **English** for all code, comments, and commit messages
- Write meaningful commit messages in conventional format (e.g., `feat:`, `fix:`, `chore:`)

## Project Structure

```
src/
├── ConsoleProgressBar.php    # Main progress bar class
└── Output/
    ├── OutputInterface.php   # Output handler contract
    ├── ConsoleOutput.php     # Default STDOUT handler
    ├── CallbackOutput.php    # Callback-based handler
    └── LoggerOutput.php      # PSR-3 logger adapter
tests/
└── ConsoleProgressBarTest.php  # All tests (PHPUnit)
examples/                       # Usage examples
```

## Making Changes

1. Create a feature branch:
   ```bash
   git checkout -b feat/my-feature
   ```
2. Make your changes and add/update tests
3. Run `composer check` to verify everything passes
4. Commit your changes

## Commit Messages

Use conventional commits:

- `feat:` — new feature
- `fix:` — bug fix
- `chore:` — maintenance, dependencies, tooling
- `docs:` — documentation only
- `test:` — adding or improving tests
- `refactor:` — code change with no functional change

Example:
```
feat: add support for custom time format
fix: handle negative limit values correctly
docs: update README with new config options
```

## Pull Request Process

1. Ensure `composer check` passes (PHPStan + PHPUnit)
2. Add tests for any new functionality
3. Update `README.md` if the public API changes
4. Update `CHANGELOG.md` with your changes under `[Unreleased]`
5. Submit the PR with a clear description of the changes

## Reporting Issues

- Use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.yml)
- Include PHP version, library version, and steps to reproduce
- For feature requests, use the [feature request template](.github/ISSUE_TEMPLATE/feature_request.yml)

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
