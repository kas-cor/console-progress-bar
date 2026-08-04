<?php

declare(strict_types=1);

namespace KasCor\Tests;

use KasCor\ConsoleProgressBar;
use PHPUnit\Framework\TestCase;

class ConsoleProgressBarTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $progressBar = new ConsoleProgressBar(100);
        $this->assertInstanceOf(ConsoleProgressBar::class, $progressBar);
    }

    public function testOutput(): void
    {
        $progressBar = new ConsoleProgressBar(1);
        ob_start();
        $progressBar->output(1);
        $output = ob_get_clean();
        $this->assertStringContainsString("1/1", $output);
    }

    /**
     * Critical #1: config injection — internal state keys must be rejected.
     */
    public function testRejectsInternalStateConfigKeys(): void
    {
        $this->expectException(\RuntimeException::class);
        new ConsoleProgressBar(100, ['startTime' => 9999999999]);
    }

    public function testRejectsUnknownConfigKeys(): void
    {
        $this->expectException(\RuntimeException::class);
        new ConsoleProgressBar(100, ['nonExistentKey' => 'value']);
    }

    /**
     * Critical #2: empty spinnerChars must not cause division by zero.
     */
    public function testEmptySpinnerCharsDoesNotThrow(): void
    {
        $progressBar = new ConsoleProgressBar(2, ['spinnerChars' => []]);
        $this->expectOutputRegex('/.*/');
        ob_start();
        $progressBar->output(1);
        ob_end_clean();
        $this->assertTrue(true);
    }

    /**
     * Critical #3: finish report must show processed elements, not the limit.
     */
    public function testFinishReportShowsProcessedElements(): void
    {
        $progressBar = new ConsoleProgressBar(100);
        ob_start();
        $progressBar->output(42);
        $progressBar->finishReport();
        $output = ob_get_clean();
        $this->assertStringContainsString("Passed elements: 42", $output);
    }

    /**
     * Critical #4: limit <= 0 must throw instead of silently collapsing.
     */
    public function testLimitZeroThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ConsoleProgressBar(0);
    }

    public function testLimitNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ConsoleProgressBar(-5);
    }
}
