<?php

declare(strict_types=1);

namespace KasCor;

use InvalidArgumentException;
use KasCor\Output\ConsoleOutput;
use KasCor\Output\OutputInterface;
use RuntimeException;

/**
 * Console progress bar with customizable display elements, spinner, and finish report.
 *
 * Supports multiple output handlers (console, PSR-3 logger, callback) via OutputInterface.
 * Each display element (bar, spinner, percent, time, position) can be individually toggled.
 *
 * @link https://github.com/kas-cor/console-progress-bar
 */
class ConsoleProgressBar
{
    /** Seconds in one minute */
    private const int SECONDS_IN_MINUTE = 60;

    /** Seconds in one hour */
    private const int SECONDS_IN_HOUR = 3600;

    /** Seconds in one day */
    private const int SECONDS_IN_DAY = 86400;

    /** @var float Unix timestamp when the progress bar was created */
    private float $startTime;

    /** @var float Total progress limit */
    private float $limit;

    /** @var int Current progress position (0 to limit) */
    private int $currentPosition = 0;

    /** @var int Length of the last printed progress string (used for overwriting) */
    private int $lastStringLength = 0;

    /** @var int Counter for spinner animation cycle */
    private int $spinnerCounter = 0;
    private ?float $finishTime = null;

    /** @var int Number of message lines printed (used for cursor positioning) */
    private int $messageCount = 0;

    /** Size of the progress bar in characters */
    public int $progressBarSize = 50 {
        set(int $value) {
            if ($value < 0) {
                throw new InvalidArgumentException('progressBarSize must be a non-negative integer, got ' . $value);
            }
            $this->progressBarSize = $value;
        }
    }

    /** Whether to show timestamp before custom messages */
    public bool $showTimeMessage = true;

    /** Whether to show the progress bar [###...] */
    public bool $showBar = true;

    /** Whether to show current position (e.g., 005/100) */
    public bool $showCurrentPosition = true;

    /** Whether to show the spinner animation */
    public bool $showSpinner = true;

    /** Whether to show percentage progress (e.g., 50.00%) */
    public bool $showPercent = true;

    /** Whether to show passed time */
    public bool $showPassedTime = true;

    /** Whether to show estimated remaining time */
    public bool $showEstimatedTime = true;

    /** Whether to show the finish report when progress reaches the limit */
    public bool $showFinishReport = true;

    /** Date/time format for message timestamps and finish report (PHP date format) */
    public string $timeMessageFormat = 'd.m.Y H:i:s';

    /** Character used for filled portion of the progress bar */
    public string $progressBarFullChar = '#';

    /** Character used for empty portion of the progress bar */
    public string $progressBarEmptyChar = '.';

    /** @var list<string> Characters to cycle through for the spinner animation */
    public array $spinnerChars = ['-', '\\', '|', '/'];

    /** Separator between display elements */
    public string $separator = ' - ';

    /** @var list<string> Ordered list of display element keys */
    public array $orderElements = ['spinner', 'progress_bar', 'current_position', 'percent', 'passed_time', 'estimated_time'];

    /**
     * @param int                  $limit  Total number of items to process (must be positive)
     * @param array<string, mixed> $config Optional configuration overrides (property => value pairs)
     * @param OutputInterface      $output Custom output handler; defaults to ConsoleOutput
     *
     * @throws InvalidArgumentException If the limit is not positive or a config value has the wrong type
     * @throws RuntimeException         If config contains an unknown property key
     */
    public function __construct(
        int $limit,
        array $config = [],
        private OutputInterface $output = new ConsoleOutput(),
    ) {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be a positive integer, got ' . $limit);
        }

        $this->limit = (float) $limit;
        $this->startTime = \microtime(true);

        foreach ($config as $key => $value) {
            match ($key) {
                'showTimeMessage' => $this->showTimeMessage = $this->assertConfigBool($key, $value),
                'showBar' => $this->showBar = $this->assertConfigBool($key, $value),
                'showCurrentPosition' => $this->showCurrentPosition = $this->assertConfigBool($key, $value),
                'showSpinner' => $this->showSpinner = $this->assertConfigBool($key, $value),
                'showPercent' => $this->showPercent = $this->assertConfigBool($key, $value),
                'showPassedTime' => $this->showPassedTime = $this->assertConfigBool($key, $value),
                'showEstimatedTime' => $this->showEstimatedTime = $this->assertConfigBool($key, $value),
                'showFinishReport' => $this->showFinishReport = $this->assertConfigBool($key, $value),
                'timeMessageFormat' => $this->timeMessageFormat = $this->assertConfigString($key, $value),
                'progressBarSize' => $this->progressBarSize = $this->assertConfigInt($key, $value),
                'progressBarFullChar' => $this->progressBarFullChar = $this->assertConfigString($key, $value),
                'progressBarEmptyChar' => $this->progressBarEmptyChar = $this->assertConfigString($key, $value),
                'spinnerChars' => $this->spinnerChars = $this->assertConfigStringList($key, $value),
                'separator' => $this->separator = $this->assertConfigString($key, $value),
                'orderElements' => $this->orderElements = $this->assertConfigStringList($key, $value),
                default => throw new RuntimeException('Config key "' . $key . '" not found!'),
            };
        }
    }

    /**
     * Update and output the progress bar.
     *
     * Prints the current progress state (spinner, bar, position, percent, times)
     * to the configured output handler. When progress reaches the limit and
     * showFinishReport is enabled, also prints the finish report.
     *
     * @param int|null    $currentPosition Current progress position (null to keep previous)
     * @param string|null $message         Optional message to print before the progress bar
     */
    public function output(?int $currentPosition = null, ?string $message = null): void
    {
        $this->currentPosition = $currentPosition ?? $this->currentPosition;

        if ($message) {
            if ($this->messageCount > 0) {
                $this->output->write("\033[1L");
            }

            $msg = '';

            if ($this->showTimeMessage) {
                $msg .= \date($this->timeMessageFormat) . $this->separator;
            }
            $msg .= $message;
            $this->output->write($msg . "\n");
            $this->messageCount++;
        }

        $result = $this->getProgressString();

        if ($this->lastStringLength > 0) {
            $this->output->write(\str_repeat(' ', $this->lastStringLength) . "\r" . $result . "\r");
        } else {
            $this->output->write($result . "\r");
        }
        $this->lastStringLength = \mb_strlen($result);

        if ($this->currentPosition === (int) $this->limit) {
            $this->finishTime = $this->finishTime ?? \microtime(true);

            if ($this->showFinishReport) {
                $this->output->write(\str_repeat(' ', $this->lastStringLength) . "\r");
                $this->output->write($this->getReportString() . \PHP_EOL);
            }
        }
    }

    /**
     * Get the current progress data.
     *
     * Returns an associative array with the current limit, position,
     * percentage, passed time, and estimated remaining time.
     *
     * @return array{
     *     limit: float,
     *     current_position: int,
     *     percent: float,
     *     passed_time: array{days: int, hours: int, minutes: int, seconds: int},
     *     estimated_time: array{days: int, hours: int, minutes: int, seconds: int},
     * }
     */
    public function getProgressData(): array
    {
        $percent = $this->currentPosition / $this->limit * 100;
        $progressTime = \microtime(true) - $this->startTime;
        // Clamp to 0 so a position beyond the limit (percent > 100) cannot produce a negative estimate
        $estimatedTime = \max(0.0, $percent > 0 ? $progressTime / $percent * (100 - $percent) : 0.0);

        return [
            'limit' => $this->limit,
            'current_position' => $this->currentPosition,
            'percent' => $percent,
            'passed_time' => $this->formatTime($progressTime),
            'estimated_time' => $this->formatTime($estimatedTime),
        ];
    }

    /**
     * Print the finish report.
     *
     * Outputs a summary with start time, finish time, total processed items,
     * and total elapsed time. Respects the showFinishReport flag.
     */
    public function finishReport(): void
    {
        if (!$this->showFinishReport) {
            return;
        }

        $this->output->write(\str_repeat(' ', $this->lastStringLength) . "\r");
        $this->output->write($this->getReportString() . \PHP_EOL);
    }

    /**
     * Set a custom output handler.
     *
     * @param OutputInterface $output The output handler to use
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Get the current output handler.
     *
     * @return OutputInterface The active output handler
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Build the progress bar string with all enabled elements.
     *
     * Constructs a string containing the spinner, progress bar, position,
     * percentage, passed time, and estimated time in the configured order.
     *
     * @return string The formatted progress string (trailing with padding spaces)
     */
    private function getProgressString(): string
    {
        $progress = $this->getProgressData();
        /** @var array<string, string> $output */
        $output = [];

        if ($this->showSpinner && $this->spinnerChars !== []) {
            $spinner = ++$this->spinnerCounter % \count($this->spinnerChars);
            $output['spinner'] = $this->spinnerChars[$spinner];
        }

        if ($this->showBar) {
            $size = $progress['percent'] / 100 * $this->progressBarSize;
            $fullCount = \max(0, \min((int) $size, $this->progressBarSize));
            $emptyCount = \max(0, $this->progressBarSize - $fullCount);
            $output['progress_bar'] = '[' . \str_repeat($this->progressBarFullChar, $fullCount) . \str_repeat($this->progressBarEmptyChar, $emptyCount) . ']';
        }

        if ($this->showCurrentPosition) {
            $width = \mb_strlen((string) $this->limit);
            $output['current_position'] = \sprintf('%0' . $width . 'd', $progress['current_position']) . '/' . \sprintf('%0' . $width . 'd', $this->limit);
        }

        if ($this->showPercent) {
            $output['percent'] = \sprintf('%05.2F', $progress['percent']) . '%';
        }

        if ($this->showPassedTime) {
            $output['passed_time'] = 'passed: ' . ($this->getTimeString($progress['passed_time']) ?: 'now');
        }

        if ($this->showEstimatedTime) {
            $output['estimated_time'] = 'estimated: ' . ($this->getTimeString($progress['estimated_time']) ?: 'now');
        }

        $result = [];

        foreach ($this->orderElements as $order) {
            if (isset($output[$order])) {
                $result[] = $output[$order];
            }
        }

        return \implode($this->separator, $result) . \str_repeat(' ', 5);
    }

    /**
     * Format a time array into a compact human-readable string.
     *
     * Converts time components (days, hours, minutes, seconds) into an
     * abbreviated format like "1d 2h 30m 15s". Omits zero-valued components.
     *
     * @param array{days: int, hours: int, minutes: int, seconds: int} $progressTime Time components
     *
     * @return string Formatted time string (e.g., "1d 2h 30m 15s") or empty string if all zero
     */
    private function getTimeString(array $progressTime): string
    {
        $abbreviations = ['day' => 'd', 'hour' => 'h', 'minute' => 'm', 'second' => 's'];
        $result = '';

        foreach (['day', 'hour', 'minute', 'second'] as $div) {
            $key = $div . 's';
            $result .= ($progressTime[$key] ? $progressTime[$key] . $abbreviations[$div] . ' ' : '');
        }

        return \mb_trim($result);
    }

    /**
     * Build the finish report string.
     *
     * Contains start date/time, finish date/time, total processed elements,
     * and total elapsed time.
     *
     * @return string The formatted finish report (multi-line)
     */
    private function getReportString(): string
    {
        $progress = $this->getProgressData();

        $result = '=================' . \PHP_EOL;
        $result .= 'Start: ' . \date($this->timeMessageFormat, (int) $this->startTime) . \PHP_EOL;
        $result .= 'Finish: ' . \date($this->timeMessageFormat, (int) ($this->finishTime ?? \microtime(true))) . \PHP_EOL;
        $result .= 'Passed elements: ' . $this->currentPosition . \PHP_EOL;
        $result .= 'Passed time: ' . ($this->getTimeString($progress['passed_time']) ?: 'now') . \PHP_EOL;

        return $result;
    }

    /**
     * Decompose a duration in seconds into days, hours, minutes, and seconds.
     *
     * @param float $time Duration in seconds (from microtime difference)
     *
     * @return array{days: int, hours: int, minutes: int, seconds: int} Time components
     */
    private function formatTime(float $time): array
    {
        $days = (int) \floor($time / self::SECONDS_IN_DAY);
        $time -= $days * self::SECONDS_IN_DAY;
        $hours = (int) \floor($time / self::SECONDS_IN_HOUR);
        $time -= $hours * self::SECONDS_IN_HOUR;
        $minutes = (int) \floor($time / self::SECONDS_IN_MINUTE);
        $time -= $minutes * self::SECONDS_IN_MINUTE;
        $seconds = (int) \floor($time);

        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }

    /**
     * Validate that a config value is a bool.
     *
     * @param string $key   Config key (used in the error message)
     * @param mixed  $value Value to validate
     *
     * @throws InvalidArgumentException When the value is not a bool
     */
    private function assertConfigBool(string $key, mixed $value): bool
    {
        if (!\is_bool($value)) {
            throw new InvalidArgumentException('Config key "' . $key . '" must be of type bool, ' . \get_debug_type($value) . ' given');
        }

        return $value;
    }

    /**
     * Validate that a config value is a string.
     *
     * @param string $key   Config key (used in the error message)
     * @param mixed  $value Value to validate
     *
     * @throws InvalidArgumentException When the value is not a string
     */
    private function assertConfigString(string $key, mixed $value): string
    {
        if (!\is_string($value)) {
            throw new InvalidArgumentException('Config key "' . $key . '" must be of type string, ' . \get_debug_type($value) . ' given');
        }

        return $value;
    }

    /**
     * Validate that a config value is an int.
     *
     * @param string $key   Config key (used in the error message)
     * @param mixed  $value Value to validate
     *
     * @throws InvalidArgumentException When the value is not an int
     */
    private function assertConfigInt(string $key, mixed $value): int
    {
        if (!\is_int($value)) {
            throw new InvalidArgumentException('Config key "' . $key . '" must be of type int, ' . \get_debug_type($value) . ' given');
        }

        return $value;
    }

    /**
     * Validate that a config value is a list of strings.
     *
     * @param string $key   Config key (used in the error message)
     * @param mixed  $value Value to validate
     *
     * @return list<string> The validated list
     *
     * @throws InvalidArgumentException When the value is not a list of strings
     */
    private function assertConfigStringList(string $key, mixed $value): array
    {
        if (!\is_array($value) || !\array_is_list($value)) {
            throw new InvalidArgumentException('Config key "' . $key . '" must be of type list<string>, ' . \get_debug_type($value) . ' given');
        }

        foreach ($value as $item) {
            if (!\is_string($item)) {
                throw new InvalidArgumentException('Config key "' . $key . '" must be a list of strings, got a non-string element');
            }
        }

        return $value;
    }
}
