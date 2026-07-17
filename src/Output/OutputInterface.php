<?php

declare(strict_types=1);

namespace KasCor\Output;

/**
 * Interface for output handling in ConsoleProgressBar.
 *
 * Implementations define how progress bar output is delivered — to the console,
 * a PSR-3 logger, a callback, or any other destination.
 *
 * @see ConsoleOutput  Default implementation using STDOUT
 * @see CallbackOutput Callback-based implementation
 * @see LoggerOutput   PSR-3 logger adapter
 */
interface OutputInterface
{
    /**
     * Write a string to the output without appending a trailing newline.
     *
     * Used for inline progress bar updates that overwrite the current line
     * via carriage return (\r).
     *
     * @param string $text The text to write
     */
    public function write(string $text): void;

    /**
     * Write a string to the output followed by a newline.
     *
     * Used for messages and the finish report.
     *
     * @param string $text The text to write (empty string writes just a newline)
     */
    public function writeln(string $text = ''): void;
}
