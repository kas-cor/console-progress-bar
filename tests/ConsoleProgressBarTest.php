<?php

declare(strict_types=1);

namespace KasCor\Tests;

use InvalidArgumentException;
use KasCor\ConsoleProgressBar;
use KasCor\Output\CallbackOutput;
use KasCor\Output\ConsoleOutput;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

class ConsoleProgressBarTest extends TestCase
{
    // ====================================================
    // Basic instance creation
    // ====================================================

    public function testCanCreateInstance(): void
    {
        $progressBar = new ConsoleProgressBar(100);
        $this->assertInstanceOf(ConsoleProgressBar::class, $progressBar);
    }

    public function testDefaultOutputIsConsoleOutput(): void
    {
        $progressBar = new ConsoleProgressBar(100);
        $this->assertInstanceOf(ConsoleOutput::class, $progressBar->getOutput());
    }

    public function testCustomOutputViaConstructor(): void
    {
        $output = new ConsoleOutput();
        $progressBar = new ConsoleProgressBar(1, [], $output);
        $this->assertSame($output, $progressBar->getOutput());
    }

    // ====================================================
    // ConsoleOutput tests
    // ====================================================

    public function testConsoleOutputWrite(): void
    {
        $output = new ConsoleOutput();
        \ob_start();
        $output->write('hello');
        $result = (string) \ob_get_clean();
        $this->assertSame('hello', $result);
    }

    public function testConsoleOutputWritelnWithText(): void
    {
        $output = new ConsoleOutput();
        \ob_start();
        $output->writeln('hello');
        $result = (string) \ob_get_clean();
        $this->assertSame('hello' . \PHP_EOL, $result);
    }

    public function testConsoleOutputWritelnWithEmptyString(): void
    {
        $output = new ConsoleOutput();
        \ob_start();
        $output->writeln('');
        $result = (string) \ob_get_clean();
        $this->assertSame(\PHP_EOL, $result);
    }

    public function testConsoleOutputWritelnDefault(): void
    {
        $output = new ConsoleOutput();
        \ob_start();
        $output->writeln();
        $result = (string) \ob_get_clean();
        $this->assertSame(\PHP_EOL, $result);
    }

    public function testConsoleOutputMultipleWrites(): void
    {
        $output = new ConsoleOutput();
        \ob_start();
        $output->write('a');
        $output->write('b');
        $output->writeln('c');
        $result = (string) \ob_get_clean();
        $this->assertSame('abc' . \PHP_EOL, $result);
    }

    public function testSetOutputAfterConstruction(): void
    {
        $progressBar = new ConsoleProgressBar(5);

        $captured = '';
        $progressBar->setOutput(new CallbackOutput(function (string $text) use (&$captured): void {
            $captured .= $text;
        }));

        $this->assertInstanceOf(CallbackOutput::class, $progressBar->getOutput());

        $progressBar->output(5);
        $this->assertStringContainsString('5/5', $captured);
    }

    // ====================================================
    // Edge cases for limit parameter
    // ====================================================

    public function testLimitOne(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringContainsString('1/1', $captured);
        $this->assertStringContainsString('100.00%', $captured);
    }

    public function testLimitLarge(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(100000, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(50000);
        $this->assertStringContainsString('50000/100000', $captured);
        $this->assertStringContainsString('50.00%', $captured);
    }

    // ====================================================
    // Output() with various position scenarios
    // ====================================================

    public function testOutputWithPosition(): void
    {
        $bar = new ConsoleProgressBar(5);
        \ob_start();
        $bar->output(3);
        $output = (string) \ob_get_clean();
        $this->assertStringContainsString('3/5', $output);
    }

    public function testOutputWithoutPositionUsesPreviousPosition(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(5, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(3);
        $captured = ''; // reset
        $bar->output();  // should use last position (3)
        $this->assertStringContainsString('3/5', $captured);
    }

    public function testOutputNullPositionUsesPreviousPosition(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(5, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(3);
        $captured = '';
        $bar->output(null);
        $this->assertStringContainsString('3/5', $captured);
    }

    public function testOutputPositionExceedsLimit(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(5, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(10);
        // Position 10 with limit 5: percent = 10/5*100 = 200%
        $this->assertStringContainsString('10/5', $captured);
        $this->assertStringContainsString('200.00%', $captured);
        // Negative estimate is clamped to zero instead of rendering garbage time
        $this->assertStringContainsString('estimated: now', $captured);
    }

    public function testOutputPositionAtLimitTriggersFinishReport(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(3, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(3);
        $this->assertStringContainsString('Start:', $captured);
        $this->assertStringContainsString('Finish:', $captured);
        $this->assertStringContainsString('Passed elements: 3', $captured);
    }

    public function testOutputPositionBelowLimitDoesNotTriggerFinishReport(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(3, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(2);
        $this->assertStringNotContainsString('Start:', $captured);
        $this->assertStringNotContainsString('Finish:', $captured);
    }

    public function testMultipleIncrementalOutputs(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(3, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));

        $bar->output(1);
        $this->assertStringContainsString('1/3', $captured);
        $this->assertStringNotContainsString('Finish:', $captured);

        $bar->output(2);
        $this->assertStringContainsString('2/3', $captured);

        $bar->output(3);
        $this->assertStringContainsString('3/3', $captured);
        $this->assertStringContainsString('Finish:', $captured);
    }

    // ====================================================
    // Configuration edge cases
    // ====================================================

    public function testInvalidConfigKeyThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config key "nonExistentKey" not found!');
        new ConsoleProgressBar(5, ['nonExistentKey' => 'value']);
    }

    public function testCustomProgressBarSize(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'progressBarSize' => 10,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        // 10 full chars
        $this->assertStringContainsString('[##########]', $captured);
    }

    public function testCustomProgressBarChars(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'progressBarFullChar' => '=',
            'progressBarEmptyChar' => '_',
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringContainsString('[', $captured);
        $this->assertStringNotContainsString('#', $captured);
        // At 100%, progress bar should be all '=' (full chars), no '_' (empty chars)
        $this->assertStringNotContainsString('_', $captured);
        $this->assertStringContainsString('=', $captured);
    }

    public function testCustomSeparator(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'separator' => ' | ',
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringContainsString(' | ', $captured);
    }

    public function testCustomSpinnerChars(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'spinnerChars' => ['◐', '◓', '◑', '◒'],
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        // spinnerCounter becomes 1, 1 % 4 = 1 → spinnerChars[1] = '◓'
        $this->assertStringContainsString('◓', $captured);
    }

    public function testCustomTimeFormat(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showTimeMessage' => true,
            'timeMessageFormat' => 'Y-m-d',
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1, 'hello');
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $captured);
    }

    public function testCustomOrderElements(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'orderElements' => ['percent', 'current_position', 'progress_bar'],
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);

        $percentPos = \strpos($captured, '100.00%');
        $posPos = \strpos($captured, '1/1');
        $this->assertNotFalse($percentPos);
        $this->assertNotFalse($posPos);
        $this->assertLessThan($posPos, $percentPos);
    }

    public function testEmptyOrderElements(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'orderElements' => [],
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        // Should output only the trailing spaces
        $this->assertStringNotContainsString('1/1', $captured);
        $this->assertStringNotContainsString('progress_bar', $captured);
    }

    // ====================================================
    // showTimeMessage
    // ====================================================

    public function testShowTimeMessageEnabledWithMessage(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showTimeMessage' => true,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1, 'test message');
        // Should contain date
        $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4}/', $captured);
    }

    public function testShowTimeMessageDisabledWithMessage(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showTimeMessage' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1, 'test message');
        // Message should be present in output
        $this->assertStringContainsString('test message', $captured);
        // Progress bar should also be present
        $this->assertStringContainsString('100.00%', $captured);
    }

    public function testOutputWithMessageContainsMessage(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(5, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(3, 'processing item');
        $this->assertStringContainsString('processing item', $captured);
    }

    public function testMultipleMessagesRepositionCursor(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(5, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1, 'first');
        $bar->output(2, 'second');

        // The second message is preceded by a cursor-repositioning escape sequence
        $this->assertStringContainsString("\033[1L", $captured);
    }

    // ====================================================
    // getProgressData() tests
    // ====================================================

    public function testGetProgressDataAtZeroPercent(): void
    {
        $bar = new ConsoleProgressBar(100);
        $data = $bar->getProgressData();

        $this->assertSame(100.0, $data['limit']);
        $this->assertSame(0, $data['current_position']);
        $this->assertEqualsWithDelta(0.0, $data['percent'], 0.001);
        $this->assertArrayHasKey('days', $data['passed_time']);
        $this->assertArrayHasKey('hours', $data['passed_time']);
        $this->assertArrayHasKey('minutes', $data['passed_time']);
        $this->assertArrayHasKey('seconds', $data['passed_time']);
    }

    public function testGetProgressDataAtFiftyPercent(): void
    {
        $bar = new ConsoleProgressBar(200);
        $bar->output(100);
        $data = $bar->getProgressData();

        $this->assertSame(200.0, $data['limit']);
        $this->assertSame(100, $data['current_position']);
        $this->assertEqualsWithDelta(50.0, $data['percent'], 0.01);
    }

    public function testGetProgressDataAtOneHundredPercent(): void
    {
        $bar = new ConsoleProgressBar(50);
        $bar->output(50);
        $data = $bar->getProgressData();

        $this->assertSame(50.0, $data['limit']);
        $this->assertSame(50, $data['current_position']);
        $this->assertEqualsWithDelta(100.0, $data['percent'], 0.01);
    }

    public function testGetProgressDataTimeStructure(): void
    {
        $bar = new ConsoleProgressBar(100);
        $bar->output(50);
        $data = $bar->getProgressData();

        foreach (['passed_time', 'estimated_time'] as $key) {
            $time = $data[$key];
            $this->assertArrayHasKey('days', $time);
            $this->assertArrayHasKey('hours', $time);
            $this->assertArrayHasKey('minutes', $time);
            $this->assertArrayHasKey('seconds', $time);
        }
    }

    // ====================================================
    // Spinner tests
    // ====================================================

    public function testSpinnerAdvancesOnEachOutput(): void
    {
        $outputs = [];
        $bar = new ConsoleProgressBar(5, [], new CallbackOutput(function (string $t) use (&$outputs): void {
            $outputs[] = $t;
        }));

        $bar->output(1);
        $bar->output(2);
        $bar->output(3);

        // First call: spinnerCounter becomes 1, 1%4=1 → spinnerChars[1] = '\'
        // Second call: spinnerCounter becomes 2, 2%4=2 → spinnerChars[2] = '|'
        // Third call: spinnerCounter becomes 3, 3%4=3 → spinnerChars[3] = '/'
        $this->assertStringContainsString('\\', $outputs[0]); // spinnerChars[1]: backslash
        $this->assertStringContainsString('|', $outputs[1]);  // spinnerChars[2]: pipe
        $this->assertStringContainsString('/', $outputs[2]);  // spinnerChars[3]: forward slash
    }

    public function testSpinnerWrapsAroundAfterFullCycle(): void
    {
        $outputs = [];
        $bar = new ConsoleProgressBar(5, [], new CallbackOutput(function (string $t) use (&$outputs): void {
            $outputs[] = $t;
        }));

        // 4 calls for full cycle
        $bar->output(1); // 1%4=1 → '\'
        $bar->output(2); // 2%4=2 → '|'
        $bar->output(3); // 3%4=3 → '/'
        $bar->output(4); // 4%4=0 → '-'

        $this->assertStringContainsString('-', $outputs[3]);

        // 5th call wraps to start
        $bar->output(5); // 5%4=1 → '\'
        $this->assertStringContainsString('\\', $outputs[4]);
    }

    public function testShowSpinnerDisabled(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showSpinner' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);

        // With showSpinner=false, progress bar is the first element → starts with '['
        $this->assertStringStartsWith('[', $captured);
    }

    // ====================================================
    // Estimated time tests
    // ====================================================

    public function testEstimatedTimeAtStartIsNow(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(100, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(0);
        $this->assertStringContainsString('estimated: now', $captured);
    }

    public function testEstimatedTimeAtEndIsNow(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(100, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(100);
        $this->assertStringContainsString('estimated: now', $captured);
    }

    public function testEstimatedTimeAtFiftyPercent(): void
    {
        $bar = new ConsoleProgressBar(100);
        $bar->output(50);
        /** @var array{passed_time: array{days: int, hours: int, minutes: int, seconds: int}, estimated_time: array{days: int, hours: int, minutes: int, seconds: int}} $data */
        $data = $bar->getProgressData();

        // At 50%, estimated should be roughly equal to passed time
        $passedSeconds = $data['passed_time']['seconds']
            + $data['passed_time']['minutes'] * 60
            + $data['passed_time']['hours'] * 3600
            + $data['passed_time']['days'] * 86400;

        $estimatedSeconds = $data['estimated_time']['seconds']
            + $data['estimated_time']['minutes'] * 60
            + $data['estimated_time']['hours'] * 3600
            + $data['estimated_time']['days'] * 86400;

        // passed ≈ estimated at 50% (within 1 second tolerance)
        $this->assertEqualsWithDelta($passedSeconds, $estimatedSeconds, 1.0);
    }

    // ====================================================
    // Passed time tests
    // ====================================================

    public function testPassedTimeAtStartIsNow(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(100, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(0);
        $this->assertStringContainsString('passed: now', $captured);
    }

    public function testShowPassedTimeDisabled(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showPassedTime' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringNotContainsString('passed:', $captured);
    }

    // ====================================================
    // Show/hide elements individually
    // ====================================================

    public function testShowBarDisabled(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showBar' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringNotContainsString('[#', $captured);
        $this->assertStringNotContainsString('[', $captured);
    }

    public function testShowCurrentPositionDisabled(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showCurrentPosition' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringNotContainsString('1/1', $captured);
    }

    public function testShowPercentDisabled(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showPercent' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringNotContainsString('%', $captured);
    }

    public function testShowEstimatedTimeDisabled(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showEstimatedTime' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringNotContainsString('estimated:', $captured);
    }

    public function testAllElementsDisabledExceptFinishReport(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'showBar' => false,
            'showCurrentPosition' => false,
            'showSpinner' => false,
            'showPercent' => false,
            'showPassedTime' => false,
            'showEstimatedTime' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);

        // Only finish report should appear
        $this->assertStringContainsString('Start:', $captured);
        $this->assertStringContainsString('Finish:', $captured);
        $this->assertStringNotContainsString('[', $captured);
        $this->assertStringNotContainsString('1/1', $captured);
    }

    // ====================================================
    // finishReport() method
    // ====================================================

    public function testFinishReportMethodWithoutPriorOutput(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(10, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->finishReport();
        $this->assertStringContainsString('Start:', $captured);
        $this->assertStringContainsString('Finish:', $captured);
        $this->assertStringContainsString('Passed elements: 0', $captured);
    }

    public function testFinishReportShowsNowWhenNoTimeElapsed(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(10, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->finishReport();
        $this->assertStringContainsString('Passed time: now', $captured);
    }

    public function testFinishReportWithDisabledFlag(): void
    {
        $bar = new ConsoleProgressBar(5, ['showFinishReport' => false]);
        \ob_start();
        $bar->finishReport();
        $output = (string) \ob_get_clean();
        $this->assertSame('', $output);
    }

    public function testFinishReportMultipleCalls(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(3, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->finishReport();
        $firstLen = \strlen($captured);

        $bar->finishReport();
        $this->assertGreaterThan($firstLen, \strlen($captured));
    }

    // ====================================================
    // Output with disabled showFinishReport
    // ====================================================

    public function testOutputAtLimitWithFinishReportDisabled(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(3, [
            'showFinishReport' => false,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(3);
        $this->assertStringContainsString('3/3', $captured);
        $this->assertStringNotContainsString('Start:', $captured);
        $this->assertStringNotContainsString('Finish:', $captured);
    }

    // ====================================================
    // Time formatting edge cases
    // ====================================================

    public function testProgressStringFormatting(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(100, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(25);
        // Should have 25% progress bar
        $this->assertStringContainsString('25.00%', $captured);
        // Should have 25/100
        $this->assertStringContainsString('025/100', $captured);
    }

    public function testProgressStringFormattingMidProgress(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(250, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(127);
        $this->assertStringContainsString('127/250', $captured);
        $percent = 127 / 250 * 100;
        $this->assertStringContainsString(\sprintf('%05.2F', $percent) . '%', $captured);
    }

    public function testTimeFormattingWithNonZeroDuration(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(2, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));

        // Rewind the start time so the elapsed time spans 1 day + 1 hour + 1 minute + 1 second
        $reflection = new ReflectionProperty(ConsoleProgressBar::class, 'startTime');
        $reflection->setValue($bar, \microtime(true) - 90061.0);

        $bar->output(1); // 50%

        $this->assertStringContainsString('passed: 1d 1h 1m', $captured);

        $data = $bar->getProgressData();
        $this->assertSame(1, $data['passed_time']['days']);
        $this->assertSame(1, $data['passed_time']['hours']);
        $this->assertSame(1, $data['passed_time']['minutes']);
    }

    // ====================================================
    // CallbackOutput custom handler
    // ====================================================

    public function testCallbackOutputCustomHandler(): void
    {
        $captured = '';
        $callback = function (string $text) use (&$captured): void {
            $captured .= '[LOG]' . $text;
        };

        $bar = new ConsoleProgressBar(1, [], new CallbackOutput($callback));
        $bar->output(1);

        $this->assertStringContainsString('[LOG]', $captured);
        $this->assertStringContainsString('1/1', $captured);
    }

    public function testCallbackOutputWriteln(): void
    {
        $captured = '';
        $output = new CallbackOutput(function (string $text) use (&$captured): void {
            $captured .= $text;
        });

        $output->writeln('line');

        $this->assertSame('line' . \PHP_EOL, $captured);
    }

    // ====================================================
    // Percent formatting
    // ====================================================

    public function testPercentFormattingTwoDecimals(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(3, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        // 1/3 = 33.33%
        $this->assertStringContainsString('33.33%', $captured);
    }

    public function testPercentFormattingAtHundred(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        $this->assertStringContainsString('100.00%', $captured);
    }

    // ====================================================
    // Progress bar size edge cases
    // ====================================================

    public function testProgressBarSizeZero(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'progressBarSize' => 0,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        // Size 0 -> empty bar: []
        $this->assertStringContainsString('[]', $captured);
    }

    public function testProgressBarSizeCustom(): void
    {
        $captured = '';
        $bar = new ConsoleProgressBar(1, [
            'progressBarSize' => 20,
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $bar->output(1);
        // 20 full chars
        $this->assertStringContainsString('[####################]', $captured);
    }

    public function testNegativeProgressBarSizeConfigThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('progressBarSize must be a non-negative integer');
        new ConsoleProgressBar(5, ['progressBarSize' => -1]);
    }

    public function testNegativeProgressBarSizeDirectAssignmentThrows(): void
    {
        $bar = new ConsoleProgressBar(5);
        $this->expectException(InvalidArgumentException::class);
        $bar->progressBarSize = -5;
    }

    /**
     * Critical #1: config injection — internal state keys must be rejected.
     */
    public function testRejectsInternalStateConfigKeys(): void
    {
        $this->expectException(RuntimeException::class);
        new ConsoleProgressBar(100, ['startTime' => 9999999999]);
    }

    public function testRejectsUnknownConfigKeys(): void
    {
        $this->expectException(RuntimeException::class);
        new ConsoleProgressBar(100, ['nonExistentKey' => 'value']);
    }

    /**
     * Critical #2: empty spinnerChars must not cause division by zero.
     */
    public function testEmptySpinnerCharsDoesNotThrow(): void
    {
        $captured = '';
        $progressBar = new ConsoleProgressBar(2, [
            'spinnerChars' => [],
        ], new CallbackOutput(function (string $t) use (&$captured): void {
            $captured .= $t;
        }));
        $progressBar->output(1);

        // Spinner element is skipped, so the bar is the first element
        $this->assertStringStartsWith('[', $captured);
        $this->assertStringContainsString('1/2', $captured);
    }

    /**
     * Critical #3: finish report must show processed elements, not the limit.
     */
    public function testFinishReportShowsProcessedElements(): void
    {
        $progressBar = new ConsoleProgressBar(100);
        \ob_start();
        $progressBar->output(42);
        $progressBar->finishReport();
        $output = (string) \ob_get_clean();
        $this->assertStringContainsString('Passed elements: 42', $output);
    }

    public function testInvalidConfigValueTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config key "showBar" must be of type bool');
        new ConsoleProgressBar(5, ['showBar' => 'yes']);
    }

    public function testInvalidConfigListValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config key "orderElements" must be of type list<string>');
        new ConsoleProgressBar(5, ['orderElements' => ['percent' => 'x']]);
    }

    public function testInvalidConfigListElementThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a list of strings, got a non-string element');
        new ConsoleProgressBar(5, ['orderElements' => ['percent', 123]]);
    }

    public function testInvalidConfigStringValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config key "separator" must be of type string');
        new ConsoleProgressBar(5, ['separator' => 123]);
    }

    public function testInvalidConfigIntValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config key "progressBarSize" must be of type int');
        new ConsoleProgressBar(5, ['progressBarSize' => 'big']);
    }

    /**
     * Critical #4: limit <= 0 must throw instead of silently collapsing.
     */
    public function testLimitZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ConsoleProgressBar(0);
    }

    public function testLimitNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ConsoleProgressBar(-5);
    }
}
