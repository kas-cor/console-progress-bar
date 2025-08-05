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
    private const MIN_LIMIT = 0.0000001;
    private const SECONDS_IN_MINUTE = 60;
    private const SECONDS_IN_HOUR = 3600;
    private const SECONDS_IN_DAY = 86400;

    private float $startTime;
    private float $limit;
    private int $currentPosition = 0;
    private int $lastStringLength = 0;
    private int $spinnerCounter = 0;

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
    public array $spinnerChars = ['-', '\\', '|', '/'];
    public string $separator = ' - ';
    public array $orderElements = ['spinner', 'progress_bar', 'current_position', 'percent', 'passed_time', 'estimated_time'];

    public function __construct(int $limit, array $config = [])
    {
        if ($config) {
            foreach ($config as $key => $value) {
                if (property_exists(__CLASS__, $key)) {
                    $this->$key = $value;
                } else {
                    throw new RuntimeException('Config key "' . $key . '" not found!');
                }
            }
        }

        $this->limit = $limit ?: self::MIN_LIMIT;
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

        if ($this->currentPosition === (int)$this->limit) {
            if ($this->showFinishReport) {
                echo str_repeat(' ', $this->lastStringLength) . "\r";
                echo $this->getReportString() . PHP_EOL;
            }
        }
    }

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
        echo str_repeat(' ', $this->lastStringLength) . "\r";
        echo $this->getReportString() . PHP_EOL;
    }

    private function getProgressString(): string
    {
        $progress = $this->getProgressData();
        $output = [];

        if ($this->showSpinner) {
            $spinner = ++$this->spinnerCounter % count($this->spinnerChars);
            $output['spinner'] = $this->spinnerChars[$spinner];
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

    private function getTimeString(array $progressTime): string
    {
        $result = '';
        foreach (['day', 'hour', 'minute', 'second'] as $div) {
            $key = $div . 's';
            $result .= ($progressTime[$key] ? $progressTime[$key] . $div[0] . ' ' : '');
        }

        return trim($result);
    }

    private function getReportString(): string
    {
        $progress = $this->getProgressData();
        $result = '=================' . PHP_EOL;
        $result .= 'Start: ' . date($this->timeMessageFormat, (int)$this->startTime) . PHP_EOL;
        $result .= 'Finish: ' . date($this->timeMessageFormat) . PHP_EOL;
        $result .= 'Passed elements: ' . $this->limit . PHP_EOL;
        $result .= 'Passed time: ' . $this->getTimeString($progress['passed_time']) . PHP_EOL;

        return $result;
    }

    private function formatTime(float $time): array
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
