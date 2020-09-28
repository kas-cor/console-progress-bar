# Console progress bar

## Screenshots (default config)

### In process

![In process](https://github.com/kas-cor/console-progress-bar/raw/master/in_process.png)

### Finish report

![Finish report](https://github.com/kas-cor/console-progress-bar/raw/master/finish_report.png)

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
| spinnerChars | array | Chars spinner animation |  |
| separator | string | Separator elements | - |
| orderElements | array | Order elements |  spinner, progress_bar, current_position, percent, passed_time, estimated_time |
