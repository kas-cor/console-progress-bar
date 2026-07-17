<?php

declare(strict_types=1);

namespace KasCor\Output;

use Override;

/**
 * Output handler that delegates to a user-provided callback.
 *
 * Useful for capturing output for testing, logging to custom destinations,
 * or integrating with frameworks that have their own output handling.
 *
 * Example:
 * ```php
 * $output = new CallbackOutput(fn(string $text) => file_put_contents('php://stdout', $text));
 * ```
 */
class CallbackOutput implements OutputInterface
{
    /** @var callable(string): void The callback that handles output strings */
    private $callback;

    /**
     * @param callable(string): void $callback Function invoked for each output string
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Send text to the callback.
     *
     * @param string $text The text to pass to the callback
     */
    #[Override]
    public function write(string $text): void
    {
        ($this->callback)($text);
    }

    /**
     * Send text followed by a newline to the callback.
     *
     * @param string $text The text to pass to the callback (empty string sends just a newline)
     */
    #[Override]
    public function writeln(string $text = ''): void
    {
        ($this->callback)($text . \PHP_EOL);
    }
}
