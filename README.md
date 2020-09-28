# Console progress bar

## Screenshots (default config)

### In process

![In process]()

### Finish report

![Finish report]()

## Install

```bash
composer require kas-cor/console-progress-bar
```

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

### Other config

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

### Config

| Property | Default | Description |
|---|---|---|
| showTimeMessage | True | Show time before message |
| showBar | True | Show progress bar |
| showCurrentPosition | True | Show current position |
| showSpinner | True | Show spinner |
| showPercent | True | Show percent progress |
| showPassedTime | True | Show passed time |
| showEstimatedTime | True | Show estimated time |
| showFinishReport | True | Show finish report |
| timeMessageFormat | d.m.Y H:i:s | Format date/time before message, PHP format |
| progressBarSize | 50 | Size progress bar |
| progressBarFullChar | # | Char full in progress bar |
| progressBarEmptyChar | . | Char empty in progress bar |
| spinnerChars |  | Chars spinner animation |
| separator | - | Separator elements |
| orderElements | spinner, progress_bar, current_position, percent, passed_time, estimated_time | Order elements |

