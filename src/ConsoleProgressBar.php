<?php

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

    /**
     * @var float Start time
     */
    private $startTime;

    /**
     * @var int Limit elements
     */
    private $limit;

    /**
     * @var int Current position
     */
    private $currentPosition;

    /**
     * @var int Last length progress bar
     */
    private $lastStringLength;

    /**
     * @var int Spinner counter
     */
    private $spinnerCounter = 0;

    /**
     * @var bool Show time before message
     */
    public $showTimeMessage = true;

    /**
     * @var bool Show progress bar
     */
    public $showBar = true;

    /**
     * @var bool Show current position
     */
    public $showCurrentPosition = true;

    /**
     * @var bool Show spinner
     */
    public $showSpinner = true;

    /**
     * @var bool Show percent progress
     */
    public $showPercent = true;

    /**
     * @var bool Show passed time
     */
    public $showPassedTime = true;

    /**
     * @var bool Show estimated time
     */
    public $showEstimatedTime = true;

    /**
     * @var bool Show finish report
     */
    public $showFinishReport = true;

    /**
     * @var string Format date/time before message, PHP format
     */
    public $timeMessageFormat = 'd.m.Y H:i:s';

    /**
     * @var int Size progress bar
     */
    public $progressBarSize = 50;

    /**
     * @var string Char full in progress bar
     */
    public $progressBarFullChar = '#';

    /**
     * @var string Char empty in progress bar
     */
    public $progressBarEmptyChar = '.';

    /**
     * @var string[] Chars spinner animation
     */
    public $spinnerChars = ['-', '\\', '|', '/'];

    /**
     * @var string Separator elements
     */
    public $separator = ' - ';

    /**
     * @var string[] Order elements
     */
    public $orderElements = ['spinner', 'progress_bar', 'current_position', 'percent', 'passed_time', 'estimated_time'];

    /**
     * ConsoleProgressBar constructor.
     * @param int $limit Limit elements
     * @param array $config Configuration
     */
    public function __construct($limit, $config = [])
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

        $this->limit = $limit ?: 0.0000001;
        $this->startTime = microtime(true);
    }

    /**
     * Output to console
     * @param null|int $currentPosition Current position in elements
     * @param null|string $message Output message
     */
    public function output($currentPosition = null, $message = null): void
    {
        $this->currentPosition = $currentPosition ?: $this->currentPosition;

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

        if ($currentPosition === $this->limit) {
            if ($this->showFinishReport) {
                echo str_repeat(' ', $this->lastStringLength) . "\r";
                echo $this->getReportString() . PHP_EOL;
            }
        }
    }

    /**
     * Getting progress data
     * @return array
     */
    public function getProgressData(): array
    {
        $percent = $this->currentPosition / $this->limit * 100;
        $progress_time = microtime(true) - $this->startTime;
        $estimated_time = $progress_time / $percent * (100 - $percent);
        foreach (
            [
                'passed_time' => $progress_time,
                'estimated_time' => $estimated_time,
            ] as $time => $value
        ) {
            $days = floor($value / 86400);
            $value -= $days * 86400;
            $hours = floor($value / 3600);
            $value -= $hours * 3600;
            $minutes = floor($value / 60);
            $value -= $minutes * 60;
            $seconds = floor($value);

            $divided[$time] = [
                'days' => $days,
                'hours' => $hours,
                'minutes' => $minutes,
                'seconds' => $seconds,
            ];
        }

        return [
            'limit' => $this->limit,
            'current_position' => $this->currentPosition,
            'percent' => $percent,
            'passed_time' => $divided['passed_time'],
            'estimated_time' => $divided['estimated_time'],
        ];
    }

    /**
     * Output finish report
     */
    public function finishReport(): void
    {
        echo str_repeat(' ', $this->lastStringLength) . "\r";
        echo $this->getReportString() . PHP_EOL;
    }

    /**
     * Getting progress bar string
     * @return string
     */
    private function getProgressString(): string
    {
        $progress = $this->getProgressData();

        if ($this->showPassedTime || $this->showEstimatedTime) {
            $time['passed_time'] = '';
            $time['estimated_time'] = '';
            foreach (['passed_time', 'estimated_time'] as $passed_estimated_time) {
                $time[$passed_estimated_time] = $this->getTimeString($progress[$passed_estimated_time]);
            }
        }

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
            $output['current_position'] = sprintf('%0' . $width . 'd', $progress['current_position']) . '/' . sprintf('%0' . $width . 'd', $progress['limit']);
        }
        if ($this->showPercent) {
            $output['percent'] = sprintf("%05.2F", $progress['percent']) . '%';
        }
        if ($this->showPassedTime) {
            $output['passed_time'] = 'passed: ' . (trim($time['passed_time']) ?: 'now');
        }
        if ($this->showEstimatedTime) {
            $output['estimated_time'] = 'estimated: ' . (trim($time['estimated_time']) ?: 'now');
        }

        foreach ($this->orderElements as $order) {
            if (isset($output[$order])) {
                $result[] = $output[$order];
            }
        }

        return implode($this->separator, $result) . str_repeat(' ', 5);
    }

    /**
     * Getting time string
     * @param int $progressTime
     * @return string
     */
    private function getTimeString($progressTime): string
    {
        $result = '';
        foreach (['day', 'hour', 'minute', 'second'] as $div) {
            $key = $div . 's';
            $result .= ($progressTime[$key] ? $progressTime[$key] . $div[0] . ' ' : '');
        }

        return $result;
    }

    /**
     * Getting finish report
     * @return string
     */
    private function getReportString(): string
    {
        $progress = $this->getProgressData();
        $result = '=================' . PHP_EOL;
        $result .= 'Start: ' . date($this->timeMessageFormat, $this->startTime) . PHP_EOL;
        $result .= 'Finish: ' . date($this->timeMessageFormat) . PHP_EOL;
        $result .= 'Passed elements: ' . $this->limit . PHP_EOL;
        $result .= 'Passed time: ' . $this->getTimeString($progress['passed_time']) . PHP_EOL;

        return $result;
    }

}
