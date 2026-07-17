# Console progress bar

[![CI](https://github.com/kas-cor/console-progress-bar/actions/workflows/ci.yml/badge.svg)](https://github.com/kas-cor/console-progress-bar/actions/workflows/ci.yml) [![Latest Version](https://img.shields.io/packagist/v/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar) [![Total Downloads](https://img.shields.io/packagist/dt/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar) [![PHP Version](https://img.shields.io/packagist/php-v/kas-cor/console-progress-bar.svg)](https://packagist.org/packages/kas-cor/console-progress-bar)

> **English version:** [README.md](README.md)

Лёгкий и настраиваемый прогресс-бар для PHP CLI-приложений. Поддерживает анимацию спиннера, отслеживание времени, отображение процентов и сменяемые обработчики вывода (консоль, PSR-3 логгер, callback).

## Скриншоты

### В процессе

![In process](https://github.com/kas-cor/console-progress-bar/raw/main/in_process.png)

### Финальный отчёт

![Finish report](https://github.com/kas-cor/console-progress-bar/raw/main/finish_report.png)

## Возможности

- Анимация спиннера с настраиваемым набором символов
- Прогресс-бар с регулируемой шириной и символами заполнения/пустоты
- Отображение текущей позиции (`005/100`)
- Процент выполнения (`50.00%`)
- Прошедшее и оценочное оставшееся время
- Сообщения с временной меткой
- Финальный отчёт по завершении
- Сменяемый вывод — консоль, PSR-3 логгер или произвольный callback
- Полная настройка порядка и видимости элементов

## Установка

```bash
composer require kas-cor/console-progress-bar
```

## Требования

- PHP >= 8.4
- `ext-mbstring`

## Быстрый старт

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position, 'message');
    sleep(1);
}
```

## Использование

### Конфигурация по умолчанию

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position);
    sleep(1);
}
```

### Пользовательская конфигурация

```php
use KasCor\ConsoleProgressBar;

$progressBar = new ConsoleProgressBar(5, [
    'showFinishReport'    => false,
    'progressBarSize'     => 30,
    'progressBarFullChar' => '=',
    'progressBarEmptyChar' => '-',
]);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position, 'message');
    sleep(1);
}
```

### Сменяемый обработчик вывода

Прогресс-бар поддерживает сменяемые обработчики вывода через `OutputInterface`. Доступно три встроенных обработчика:

| Обработчик | Описание |
|---|---|
| `ConsoleOutput` | По умолчанию — пишет напрямую в STDOUT через `echo` |
| `CallbackOutput` | Передаёт вывод в пользовательский callback |
| `LoggerOutput` | Направляет вывод в PSR-3 логгер |

#### Callback-вывод

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

#### Вывод в PSR-3 логгер

```php
use KasCor\ConsoleProgressBar;
use KasCor\Output\LoggerOutput;

// Требуется psr/log: composer require psr/log
$psrLogger = new \Monolog\Logger('progress', [new \Monolog\Handler\StreamHandler('php://stdout')]);
$output = new LoggerOutput($psrLogger);

$progressBar = new ConsoleProgressBar(5, [], $output);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position);
    sleep(1);
}
```

#### Смена обработчика во время выполнения

```php
$progressBar->setOutput($customOutput);
$output = $progressBar->getOutput();
```

#### Свой уровень логирования

```php
$output = new LoggerOutput($logger, \Psr\Log\LogLevel::DEBUG);
```

### Без параметров

```php
$progressBar = new ConsoleProgressBar(5);
for ($i = 1; $i <= 5; $i++) {
    $progressBar->output(); // использует внутренний счётчик позиции
    sleep(1);
}
```

### Ручной финальный отчёт

```php
$progressBar = new ConsoleProgressBar(5, ['showFinishReport' => false]);
foreach (range(1, 5) as $i) {
    $progressBar->output($i);
    sleep(1);
}
$progressBar->finishReport(); // вывести вручную
```

## Справочник конфигурации

| Свойство | Тип | Описание | По умолчанию |
|---|---|---|---|
| showTimeMessage | boolean | Показывать временную метку перед сообщением | `true` |
| showBar | boolean | Показывать прогресс-бар `[###...]` | `true` |
| showCurrentPosition | boolean | Показывать текущую позицию `005/100` | `true` |
| showSpinner | boolean | Показывать анимацию спиннера | `true` |
| showPercent | boolean | Показывать процент `50.00%` | `true` |
| showPassedTime | boolean | Показывать прошедшее время | `true` |
| showEstimatedTime | boolean | Показывать оценочное оставшееся время | `true` |
| showFinishReport | boolean | Показывать финальный отчёт по завершении | `true` |
| timeMessageFormat | string | PHP формат даты для временных меток | `d.m.Y H:i:s` |
| progressBarSize | int | Ширина прогресс-бара в символах | `50` |
| progressBarFullChar | string | Символ заполненной части | `#` |
| progressBarEmptyChar | string | Символ пустой части | `.` |
| spinnerChars | array | Кадры анимации спиннера | `['-', '\\', '\|', '/']` |
| separator | string | Разделитель между элементами | ` - ` |
| orderElements | array | Порядок элементов | `['spinner', 'progress_bar', 'current_position', 'percent', 'passed_time', 'estimated_time']` |

## Справочник методов

| Метод | Описание |
|---|---|
| `output(?int $position, ?string $message)` | Обновить и отобразить прогресс-бар |
| `getProgressData(): array` | Получить текущие данные прогресса (проценты, время, позицию) |
| `finishReport(): void` | Вывести финальный отчёт вручную |
| `setOutput(OutputInterface $output): void` | Установить свой обработчик вывода |
| `getOutput(): OutputInterface` | Получить текущий обработчик вывода |

### Возвращаемое значение `getProgressData()`

```php
[
    'limit'            => float,   // всего
    'current_position' => int,     // текущая позиция
    'percent'          => float,   // 0–100
    'passed_time'      => ['days' => int, 'hours' => int, 'minutes' => int, 'seconds' => int],
    'estimated_time'   => ['days' => int, 'hours' => int, 'minutes' => int, 'seconds' => int],
]
```

## Список изменений

См. [CHANGELOG.md](CHANGELOG.md).

## Лицензия

MIT
