<?php

declare(strict_types=1);

namespace KasCor\Output;

use Override;

/**
 * Default output handler that writes directly to STDOUT using echo.
 *
 * This is the default handler used when no custom OutputInterface is provided
 * to ConsoleProgressBar. It writes all output directly to the console.
 */
class ConsoleOutput implements OutputInterface
{
    /**
     * Write raw text to STDOUT.
     *
     * @param string $text The text to output
     */
    #[Override]
    public function write(string $text): void
    {
        echo $text;
    }

    /**
     * Write text followed by a newline to STDOUT.
     *
     * @param string $text The text to output (empty string writes just a newline)
     */
    #[Override]
    public function writeln(string $text = ''): void
    {
        echo $text . \PHP_EOL;
    }
}
