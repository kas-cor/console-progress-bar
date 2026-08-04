# Code Review: console-progress-bar

> **Источник:** автоматический code review через kanban (воркер `deep-reasoner`, задача `t_4614f141`)
> **Дата отчёта:** 2026-08-02 · **Дата исправления:** 2026-08-03
> 🔴 Critical: 4 · ⚠️ Warnings: 6 · 💡 Suggestions: 6

---

Verdict: Changes Requested — 4 Critical, 6 Warnings, 6 Suggestions. Код чистый и хорошо структурирован, но есть критические дыры в безопасности конфигурации и баги в логике.

Структура


console-progress-bar/
├── src/ConsoleProgressBar.php      (182 строки) — единственный класс
├── tests/ConsoleProgressBarTest.php (26 строк) — 2 теста
├── examples/ (11 файлов)           — примеры использования
├── composer.json                   — PSR-4, PHP >=7.2
├── phpunit.xml                     — конфиг PHPUnit
├── README.md, LICENSE, .gitignore
└── .github/FUNDING.yml


Монолитная архитектура: один класс на 182 строки. Для библиотеки такого размера это допустимо, но отсутствует интерфейс — класс нельзя замокать или подменить.



🔴 Critical (4)

1. Config Injection — src/ConsoleProgressBar.php:48-49

Проблема: Конструктор принимает произвольный массив $config и устанавливает ЛЮБОЕ property, существующее в классе, включая приватные (startTime, limit, currentPosition, lastStringLength, spinnerCounter). Злоумышленник (или пользователь по ошибке) может инжектировать внутреннее состояние:

php
new ConsoleProgressBar(100, [
    'startTime' => 9999999999,     // сломает расчёт времени
    'currentPosition' => 99999,    // сломает прогресс
    'spinnerCounter' => -1,        // вызовет отрицательный modulo
]);


Решение: Добавить whitelist разрешённых конфигурационных ключей. Либо вынести конфиг в отдельный объект/интерфейс, либо проверять ключи по явному списку:

php
private const ALLOWED_CONFIG_KEYS = [
    'showTimeMessage', 'showBar', 'showCurrentPosition', 'showSpinner',
    'showPercent', 'showPassedTime', 'showEstimatedTime', 'showFinishReport',
    'timeMessageFormat', 'progressBarSize', 'progressBarFullChar',
    'progressBarEmptyChar', 'spinnerChars', 'separator', 'orderElements',
];


2. Division by Zero — src/ConsoleProgressBar.php:111

Проблема: $spinner = ++$this->spinnerCounter % count($this->spinnerChars); — если пользователь передаст 'spinnerChars' => [] в конфиге, count() вернёт 0, и произойдёт DivisionByZeroError (в PHP 8+) или E_WARNING (в PHP 7).

Решение: Валидировать spinnerChars при установке — минимум 1 элемент. Либо guard на count($this->spinnerChars) ?: 1.

3. Finish Report: неверное значение Passed elements — src/ConsoleProgressBar.php:159

Проблема: 'Passed elements: ' . $this->limit — всегда выводит лимит, а не фактическое количество обработанных элементов. Если цикл прервался досрочно (break, исключение), отчёт врёт.

Решение: 'Passed elements: ' . $this->currentPosition

4. Finish Report: неверное условие для малых лимитов — src/ConsoleProgressBar.php:56,76

Проблема: $this->limit = $limit ?: self::MIN_LIMIT; — если передать limit = 0, он молча заменяется на 0.0000001. Затем (int)0.0000001 === 0, и финиш-отчёт срабатывает при currentPosition === 0, т.е. на первом же вызове output().

Решение: Бросать \InvalidArgumentException при $limit <= 0 вместо молчаливой подмены.



⚠️ Warnings (6)

5. Нет whitelist для конфигурационных ключей — src/ConsoleProgressBar.php:48

Даже после исправления Critical #1, отсутствие валидации типов для конфигурационных значений — проблема. 'progressBarSize' => 'abc' молча сломает str_repeat.

6. Тесты — только 2 кейса, покрытие ~5%

Нет тестов на: config injection, edge cases (limit=0, negative position), time formatting, finish report, spinner, все boolean-флаги, orderElements, кастомные символы.

7. composer-runtime-api в зависимостях — composer.json:17

"composer-runtime-api": "^2.0" нужен только Composer-плагинам. Обычной библиотеке он не требуется и создаёт лишнее ограничение.

8. Хардкод английских строк — src/ConsoleProgressBar.php:126,129,157-160

'passed: ', 'estimated: ', 'now', 'Start: ', 'Finish: ', 'Passed elements: ', 'Passed time: ' — не вынесены в конфиг. Библиотеку нельзя локализовать без форка.

9. Finish Report: время завершения — src/ConsoleProgressBar.php:158

'Finish: ' . date($this->timeMessageFormat) — использует текущее время на момент вызова getReportString(), а не время, когда прогресс реально завершился. Если finishReport() вызвать вручную позже — время будет неверным.

10. Нет конфигов для PHPStan/CS-Fixer

composer.json требует phpstan/phpstan и friendsofphp/php-cs-fixer, но в репозитории нет .phpstan.neon и .php-cs-fixer.php. Инструменты заявлены, но, вероятно, никогда не запускались.

11. FUNDING.yml — obfuscated URL

.github/FUNDING.yml содержит custom: https://bit.ly/3uVaKEu — bit.ly-ссылка скрывает реальный URL. Это trust issue: пользователь не знает, куда ведёт ссылка.



💡 Suggestions (6)

12. private → protected — src/ConsoleProgressBar.php:105,165

getProgressString() и formatTime() объявлены как private. Если кто-то захочет расширить класс (например, кастомный формат времени), он не сможет переопределить эти методы. Стоит сделать protected.

13. Хрупкий трюк с $div[0] — src/ConsoleProgressBar.php:147

$div[0] для получения первой буквы ('day'[0] → 'd') работает, но ломается, если переименовать ключи массива. Лучше использовать явный mapping: ['day' => 'd', 'hour' => 'h', ...].

14. Опечатка в имени файла

examples/progress_bar_whith_percent.php — "whith" вместо "with".

15. Нет declare(strict_types=1) в примерах

Главный класс использует strict_types, примеры — нет. Несоответствие.

16. Очистка строки по длине прогресс-бара — src/ConsoleProgressBar.php:65,78

str_repeat(' ', $this->lastStringLength) очищает строку по длине предыдущего прогресс-бара, а не сообщения. Если сообщение длиннее прогресс-бара, остатки старой строки останутся видимыми.

17. Нет интерфейса

Класс нельзя замокать в тестах, нельзя подменить реализацию. Для библиотеки, которую будут использовать в проектах, интерфейс ConsoleProgressBarInterface был бы полезен.



✅ Что сделано отлично

- Чистый, читаемый код — 182 строки, всё по делу
- declare(strict_types=1) и typed properties (PHP 7.4+)
- PSR-4 autoloading, правильная структура composer-пакета
- 11 примеров покрывают все сценарии использования
- README с таблицей всех конфигурационных параметров
- MIT лицензия
- \r-based обновление строки — классический и корректный подход для консольных прогресс-баров
- Разделение на output() (основной цикл) и finishReport() (ручной вызов) — гибко



Итог

Библиотека решает свою задачу и в базовом сценарии работает. Однако config injection (Critical #1) — это реальная дыра: любой, кто передаёт пользовательский конфиг (например, из JSON-файла или переменных окружения), рискует получить манипуляцию внутренним состоянием. Critical #2 (division by zero) воспроизводится одной строкой конфига. Critical #3 и #4 — баги, искажающие финиш-отчёт.

Рекомендую: исправить 4 Critical перед любым релизом, закрыть Warnings в следующем минорном релизе, Suggestions — по возможности.

---

## Статус исправления

Исправлены 4 Critical в `src/ConsoleProgressBar.php`:
1. whitelist конфиг-ключей (`ALLOWED_CONFIG_KEYS`) — закрывает config injection;
2. защита от division-by-zero для пустого `spinnerChars` (fallback на `DEFAULT_SPINNER_CHARS`);
3. финиш-отчёт выводит `currentPosition`, а не `limit`;
4. `limit <= 0` бросает `InvalidArgumentException` вместо молчаливой подмены на `MIN_LIMIT`.

Добавлены тесты на все 4 Critical (итого 8 тестов). phpunit 8/8 OK.
