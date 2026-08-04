<?php

declare(strict_types=1);

namespace KasCor;

use RuntimeException;

/**
 * Class ConsoleProgressBar
 * @author kas-cor
 * @link https://github.com/kas-cor/console-progress-bar
 * @package KasCor
 */
class ConsoleProgressBar
{
    private const SECONDS_IN_MINUTE = 60;
    private const SECONDS_IN_HOUR = 3600;
    private const SECONDS_IN_DAY = 86400;
    private const DEFAULT_SPINNER_CHARS = ['-', '\\', '|', '/'];
    private const ALLOWED_CONFIG_KEYS = [
        'showTimeMessage',
        'showBar',
        'showCurrentPosition',
        'showSpinner',
        'showPercent',
        'showPassedTime',
        'showEstimatedTime',
        'showFinishReport',
        'timeMessageFormat',
        'progressBarSize',
        'progressBarFullChar',
        'progressBarEmptyChar',
        'spinnerChars',
        'separator',
        'orderElements',
    ];

    private float $startTime;
    private float $limit;
    private int $currentPosition = 0;
    private int $lastStringLength = 0;
    private int $spinnerCounter = 0;
    private ?float $finishTime = null;

    public bool $showTimeMessage = true;
    public bool $showBar = true;
    public bool $showCurrentPosition = true;
    public bool $showSpinner = true;
    public bool $showPercent = true;
    public bool $showPassedTime = true;
    public bool $showEstimatedTime = true;
    public bool $showFinishReport = true;
    public string $timeMessageFormat = 'd.m.Y H:i:s';
    public int $progressBarSize = 50;
    public string $progressBarFullChar = '#';
    public string $progressBarEmptyChar = '.';
    /** @var array<int, string> */
    public array $spinnerChars = ['-', '\\', '|', '/'];
    public string $separator = ' - ';
    /** @var array<int, string> */
    public array $orderElements = ['spinner', 'progress_bar', 'current_position', 'percent', 'passed_time', 'estimated_time'];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(int $limit, array $config = [])
    {
        if ($config) {
            foreach ($config as $key => $value) {
                if (in_array($key, self::ALLOWED_CONFIG_KEYS, true)) {
                    $this->$key = $value;
                } else {
                    throw new RuntimeException('Config key "' . $key . '" not found!');
                }
            }
        }

        if ($limit <= 0) {
            throw new \InvalidArgumentException('Limit must be greater than 0');
        }
        $this->limit = $limit;
        $this->startTime = microtime(true);
    }

    public function output(?int $currentPosition = null, ?string $message = null): void
    {
        $this->currentPosition = $currentPosition ?? $this->currentPosition;

        if ($message) {
            echo str_repeat(' ', $this->lastStringLength) . "\r";
            if ($this->showTimeMessage) {
                echo date($this->timeMessageFormat) . $this->separator;
            }
            echo $message . PHP_EOL;
        }

        $result = $this->getProgressString();
        echo $result . "\r";
        $this->lastStringLength = strlen($result);

        if ($this->currentPosition >= $this->limit) {
            if ($this->showFinishReport) {
                $this->finishTime = $this->finishTime ?? microtime(true);
                echo str_repeat(' ', $this->lastStringLength) . "\r";
                echo $this->getReportString() . PHP_EOL;
            }
        }
    }

    /**
     * @return array{limit: float, current_position: int, percent: float, passed_time: array{days: float, hours: float, minutes: float, seconds: float}, estimated_time: array{days: float, hours: float, minutes: float, seconds: float}}
     */
    public function getProgressData(): array
    {
        $percent = $this->currentPosition / $this->limit * 100;
        $progressTime = microtime(true) - $this->startTime;
        $estimatedTime = $percent > 0 ? $progressTime / $percent * (100 - $percent) : 0;

        return [
            'limit' => $this->limit,
            'current_position' => $this->currentPosition,
            'percent' => $percent,
            'passed_time' => $this->formatTime($progressTime),
            'estimated_time' => $this->formatTime($estimatedTime),
        ];
    }

    public function finishReport(): void
    {
        $this->finishTime = $this->finishTime ?? microtime(true);
        echo str_repeat(' ', $this->lastStringLength) . "\r";
        echo $this->getReportString() . PHP_EOL;
    }

    protected function getProgressString(): string
    {
        $progress = $this->getProgressData();
        $output = [];

        if ($this->showSpinner) {
            $spinnerChars = $this->spinnerChars ?: self::DEFAULT_SPINNER_CHARS;
            $spinner = ++$this->spinnerCounter % count($spinnerChars);
            $output['spinner'] = $spinnerChars[$spinner];
        }
        if ($this->showBar) {
            $size = $progress['percent'] / 100 * $this->progressBarSize;
            $output['progress_bar'] = '[' . str_repeat($this->progressBarFullChar, (int)$size) . str_repeat($this->progressBarEmptyChar, $this->progressBarSize - (int)$size) . ']';
        }
        if ($this->showCurrentPosition) {
            $width = strlen((string)$this->limit);
            $output['current_position'] = sprintf('%0' . $width . 'd', $progress['current_position']) . '/' . sprintf('%0' . $width . 'd', $this->limit);
        }
        if ($this->showPercent) {
            $output['percent'] = sprintf("%05.2F", $progress['percent']) . '%';
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

        return implode($this->separator, $result) . str_repeat(' ', 5);
    }

    /**
     * @param array{days: float, hours: float, minutes: float, seconds: float} $progressTime
     */
    protected function getTimeString(array $progressTime): string
    {
        $abbreviations = ['day' => 'd', 'hour' => 'h', 'minute' => 'm', 'second' => 's'];
        $result = '';
        foreach ($abbreviations as $div => $abbr) {
            $key = $div . 's';
            $result .= ($progressTime[$key] ? $progressTime[$key] . $abbr . ' ' : '');
        }

        return trim($result);
    }

    private function getReportString(): string
    {
        $progress = $this->getProgressData();
        $result = '=================' . PHP_EOL;
        $result .= 'Start: ' . date($this->timeMessageFormat, (int)$this->startTime) . PHP_EOL;
        $result .= 'Finish: ' . date($this->timeMessageFormat, (int)($this->finishTime ?? microtime(true))) . PHP_EOL;
        $result .= 'Passed elements: ' . $this->currentPosition . PHP_EOL;
        $result .= 'Passed time: ' . $this->getTimeString($progress['passed_time']) . PHP_EOL;

        return $result;
    }

    /**
     * @return array{days: float, hours: float, minutes: float, seconds: float}
     */
    protected function formatTime(float $time): array
    {
        $days = floor($time / self::SECONDS_IN_DAY);
        $time -= $days * self::SECONDS_IN_DAY;
        $hours = floor($time / self::SECONDS_IN_HOUR);
        $time -= $hours * self::SECONDS_IN_HOUR;
        $minutes = floor($time / self::SECONDS_IN_MINUTE);
        $time -= $minutes * self::SECONDS_IN_MINUTE;
        $seconds = floor($time);

        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }
}
