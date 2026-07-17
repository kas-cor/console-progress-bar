# Console progress bar

[![CI](https://github.com/kas-cor/console-progress-bar/actions/workflows/ci.yml/badge.svg)](https://github.com/kas-cor/console-progress-bar/actions/workflows/ci.yml) [![Latest Version](https://img.shields.io/packagist/v/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar) [![Total Downloads](https://img.shields.io/packagist/dt/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar) [![PHP Version](https://img.shields.io/packagist/php-v/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar)

## Screenshots (default config)

### In process

![In process](https://github.com/kas-cor/console-progress-bar/raw/main/in_process.png)

### Finish report

![Finish report](https://github.com/kas-cor/console-progress-bar/raw/main/finish_report.png)

## Install

```bash
composer require kas-cor/console-progress-bar
```

## Requirements

- PHP >= 8.4
- `ext-mbstring`

## Usage

### Default config

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position, 'message');
    sleep(1);
}
```

### Custom config

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5, [
    'showFinishReport' => false,
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

// Capture output in a variable
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

You can also change the output handler at runtime:

```php
$progressBar->setOutput($customOutput);
$output = $progressBar->getOutput();
```

And use a custom log level:

```php
$output = new LoggerOutput($logger, \Psr\Log\LogLevel::DEBUG);
```

### Config reference

| Property | Type | Description | Default |
|---|---|---|---|
| showTimeMessage | boolean | Show time before message | True |
| showBar | boolean | Show progress bar | True |
| showCurrentPosition | boolean | Show current position | True |
| showSpinner | boolean | Show spinner | True |
| showPercent | boolean | Show percent progress | True |
| showPassedTime | boolean | Show passed time | True |
| showEstimatedTime | boolean | Show estimated time | True |
| showFinishReport | boolean | Show finish report | True |
| timeMessageFormat | string | Format date/time before message, PHP format | d.m.Y H:i:s |
| progressBarSize | integer | Size progress bar | 50 |
| progressBarFullChar | string | Char full in progress bar | # |
| progressBarEmptyChar | string | Char empty in progress bar | . |
| spinnerChars | array | Chars spinner animation | ['-', '\\', '\|', '/'] |
| separator | string | Separator elements |  -  |
| orderElements | array | Order elements | spinner, progress_bar, current_position, percent, passed_time, estimated_time |

### Methods reference

| Method | Description |
|---|---|
| `output(?int $position, ?string $message)` | Update and display the progress bar |
| `getProgressData(): array` | Get current progress data (percent, times, position) |
| `finishReport(): void` | Print the finish report manually |
| `setOutput(OutputInterface $output): void` | Set a custom output handler |
| `getOutput(): OutputInterface` | Get the current output handler |
