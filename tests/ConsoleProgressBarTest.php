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
}
