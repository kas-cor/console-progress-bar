# Console progress bar

[![CI](https://github.com/kas-cor/console-progress-bar/actions/workflows/ci.yml/badge.svg)](https://github.com/kas-cor/console-progress-bar/actions/workflows/ci.yml) [![Latest Version](https://img.shields.io/packagist/v/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar) [![Total Downloads](https://img.shields.io/packagist/dt/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar) [![PHP Version](https://img.shields.io/packagist/php-v/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar)

> **Русская версия:** [README_ru.md](README_ru.md)

A lightweight, customizable progress bar for PHP CLI applications. Supports spinners, time tracking, percentage display, and pluggable output handlers (console, PSR-3 logger, callback).

## Screenshots

### In process

![In process](https://github.com/kas-cor/console-progress-bar/raw/main/in_process.png)

### Finish report

![Finish report](https://github.com/kas-cor/console-progress-bar/raw/main/finish_report.png)

## Features

- Spinner animation with customizable character set
- Progress bar with configurable size and fill/empty characters
- Current position display (`005/100`)
- Percentage progress (`50.00%`)
- Passed and estimated remaining time
- Timestamped messages
- Finish report on completion
- Pluggable output — console, PSR-3 logger, or custom callback
- Fully configurable element order and visibility

## Install

```bash
composer require kas-cor/console-progress-bar
```

## Requirements

- PHP >= 8.4
- `ext-mbstring`

## Quick start

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position, 'message');
    sleep(1);
}
```

## Usage

### Default config

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position);
    sleep(1);
}
```

### Custom config

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5, [
    'showFinishReport'  => false,
    'progressBarSize'   => 30,
    'progressBarFullChar' => '=',
    'progressBarEmptyChar' => '-',
]);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position, 'message');
    sleep(1);
}
```

### Custom output handler

The progress bar supports pluggable output handlers via `OutputInterface`. Three built-in handlers are available:

| Handler | Description |
|---|---|
| `ConsoleOutput` | Default — writes directly to STDOUT via `echo` |
| `CallbackOutput` | Delegates output to a user-provided callback |
| `LoggerOutput` | Routes output to a PSR-3 logger |

#### Callback output

```php
use KasCor\ConsoleProgressBar;
use KasCor\Output\CallbackOutput;

$captured = '';
$output = new CallbackOutput(function (string $text) use (&$captured): void {
    $captured .= $text;
});

$progressBar = new ConsoleProgressBar(5, [], $output);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position);
    sleep(1);
}
```

#### PSR-3 logger output

```php
use KasCor\ConsoleProgressBar;
use KasCor\Output\LoggerOutput;

// Requires psr/log: composer require psr/log
$psrLogger = new \Monolog\Logger('progress', [new \Monolog\Handler\StreamHandler('php://stdout')]);
$output = new LoggerOutput($psrLogger);

$progressBar = new ConsoleProgressBar(5, [], $output);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position);
    sleep(1);
}
```

#### Changing output at runtime

```php
$progressBar->setOutput($customOutput);
$output = $progressBar->getOutput();
```

#### Custom log level

```php
$output = new LoggerOutput($logger, \Psr\Log\LogLevel::DEBUG);
```

### Without parameters

```php
$progressBar = new ConsoleProgressBar(5);
for ($i = 1; $i <= 5; $i++) {
    $progressBar->output(); // uses internal position counter
    sleep(1);
}
```

### Manual finish report

```php
$progressBar = new ConsoleProgressBar(5, ['showFinishReport' => false]);
foreach (range(1, 5) as $i) {
    $progressBar->output($i);
    sleep(1);
}
$progressBar->finishReport(); // print manually
```

## Config reference

| Property | Type | Description | Default |
|---|---|---|---|
| showTimeMessage | boolean | Show timestamp before message | `true` |
| showBar | boolean | Show progress bar `[###...]` | `true` |
| showCurrentPosition | boolean | Show current position `005/100` | `true` |
| showSpinner | boolean | Show spinner animation | `true` |
| showPercent | boolean | Show percentage `50.00%` | `true` |
| showPassedTime | boolean | Show passed time | `true` |
| showEstimatedTime | boolean | Show estimated remaining time | `true` |
| showFinishReport | boolean | Show finish report on completion | `true` |
| timeMessageFormat | string | PHP date format for timestamps | `d.m.Y H:i:s` |
| progressBarSize | int | Width of the progress bar in characters | `50` |
| progressBarFullChar | string | Character for filled portion | `#` |
| progressBarEmptyChar | string | Character for empty portion | `.` |
| spinnerChars | array | Spinner animation frames | `['-', '\\', '\|', '/']` |
| separator | string | Separator between elements | ` - ` |
| orderElements | array | Element order | `['spinner', 'progress_bar', 'current_position', 'percent', 'passed_time', 'estimated_time']` |

## Methods reference

| Method | Description |
|---|---|
| `output(?int $position, ?string $message)` | Update and display the progress bar |
| `getProgressData(): array` | Get current progress data (percent, times, position) |
| `finishReport(): void` | Print the finish report manually |
| `setOutput(OutputInterface $output): void` | Set a custom output handler |
| `getOutput(): OutputInterface` | Get the current output handler |

### `getProgressData()` return value

```php
[
    'limit'           => float,   // total limit
    'current_position' => int,    // current position
    'percent'         => float,   // 0–100
    'passed_time'     => ['days' => int, 'hours' => int, 'minutes' => int, 'seconds' => int],
    'estimated_time'  => ['days' => int, 'hours' => int, 'minutes' => int, 'seconds' => int],
]
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT
