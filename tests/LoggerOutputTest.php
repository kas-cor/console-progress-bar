<?php

declare(strict_types=1);

namespace KasCor\Tests;

use KasCor\ConsoleProgressBar;
use KasCor\Output\LoggerOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerOutputTest extends TestCase
{
    public function testDefaultLogLevelIsInfo(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO, 'test message');

        $output->write('test message');
    }

    public function testWriteLogsMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with($this->anything(), 'progress update');

        $output->write('progress update');
    }

    public function testWritelnLogsMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with($this->anything(), 'completed line');

        $output->writeln('completed line');
    }

    public function testCustomLogLevel(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger, LogLevel::DEBUG);

        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, $this->anything());

        $output->write('debug test');
    }

    public function testCustomLogLevelWithWriteln(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger, LogLevel::ERROR);

        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::ERROR, $this->anything());

        $output->writeln('error test');
    }

    public function testMultipleWritesAllLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $logger->expects($this->exactly(3))
            ->method('log');

        $output->write('first');
        $output->write('second');
        $output->write('third');
    }

    public function testWriteAndWritelnAreLoggedSeparately(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $logger->expects($this->exactly(2))
            ->method('log');

        $output->write('inline');
        $output->writeln('new line');
    }

    // ====================================================
    // Integration tests with ConsoleProgressBar
    // ====================================================

    public function testIntegrationWithProgressBarLogsOutput(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        // We expect log() to be called at least once during output()
        $logger->expects($this->atLeastOnce())
            ->method('log')
            ->with(LogLevel::INFO, $this->stringContains('1/3'));

        $bar = new ConsoleProgressBar(3, [], $output);
        $bar->output(1);
    }

    public function testIntegrationWithProgressBarMultipleCalls(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        // Each output() call triggers write(), plus finish report adds more writes
        $logger->expects($this->atLeast(3))
            ->method('log');

        $bar = new ConsoleProgressBar(3, [], $output);
        $bar->output(1);
        $bar->output(2);
        $bar->output(3);
    }

    public function testIntegrationFinishReportLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $seenStart = false;
        $logger->expects($this->atLeastOnce())
            ->method('log')
            ->with(LogLevel::INFO, $this->anything())
            ->willReturnCallback(function (string $level, string $message) use (&$seenStart): void {
                if (\str_contains($message, 'Start:')) {
                    $seenStart = true;
                }
            });

        $bar = new ConsoleProgressBar(5, [], $output);
        $bar->output(5);

        $this->assertTrue($seenStart, 'Expected finish report to contain Start:');
    }

    public function testIntegrationCustomLogLevel(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger, LogLevel::NOTICE);

        $logger->expects($this->atLeastOnce())
            ->method('log')
            ->with(LogLevel::NOTICE, $this->anything());

        $bar = new ConsoleProgressBar(3, [], $output);
        $bar->output(1);
    }

    public function testFinishReportMethodLogsToLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $seenFinish = false;
        $logger->expects($this->atLeastOnce())
            ->method('log')
            ->with(LogLevel::INFO, $this->anything())
            ->willReturnCallback(function (string $level, string $message) use (&$seenFinish): void {
                if (\str_contains($message, 'Finish:')) {
                    $seenFinish = true;
                }
            });

        $bar = new ConsoleProgressBar(5, [], $output);
        $bar->finishReport();

        $this->assertTrue($seenFinish, 'Expected finish report to contain Finish:');
    }

    public function testEmptyMessageStillLogs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $output = new LoggerOutput($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with($this->anything(), '');

        $output->writeln();
    }
}
