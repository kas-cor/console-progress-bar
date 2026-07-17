<?php

declare(strict_types=1);

namespace KasCor\Output;

use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Output handler that delegates output to a PSR-3 logger.
 *
 * Progress bar updates and the finish report are logged at the configured
 * log level (default: INFO). Useful for recording progress in long-running
 * scripts or daemons without displaying output to the console.
 *
 * Requires the psr/log package:
 * ```bash
 * composer require psr/log
 * ```
 */
class LoggerOutput implements OutputInterface
{
    /** @var LoggerInterface The PSR-3 logger instance */
    private LoggerInterface $logger;

    /** @var string PSR-3 log level (e.g., LogLevel::INFO, LogLevel::DEBUG) */
    private string $level;

    /**
     * @param LoggerInterface $logger PSR-3 logger instance
     * @param string          $level  PSR-3 log level (default: LogLevel::INFO)
     */
    public function __construct(LoggerInterface $logger, string $level = LogLevel::INFO)
    {
        $this->logger = $logger;
        $this->level = $level;
    }

    /**
     * Log progress text at the configured log level.
     *
     * Note: Inline progress updates (with carriage returns) are logged as
     * separate log entries, which may be verbose for PSR-3 loggers.
     *
     * @param string $text The progress text to log
     */
    #[Override]
    public function write(string $text): void
    {
        $text = \trim($text);
        if ($text !== '') {
            $this->logger->log($this->level, $text);
        }
    }

    /**
     * Log progress text at the configured log level (with semantic newline).
     *
     * @param string $text The progress text to log (empty string logs just an empty message)
     */
    #[Override]
    public function writeln(string $text = ''): void
    {
        $this->logger->log($this->level, $text);
    }
}
