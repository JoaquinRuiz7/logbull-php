<?php

declare(strict_types=1);

namespace LogBull\Handlers;

use LogBull\Core\LogBullLogger;
use LogBull\Core\Types;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * PSR-3 logger wrapper for LogBull
 */
class PSR3Logger implements LoggerInterface
{
    private LogBullLogger $logger;

    public function __construct(
        ?string $projectId = null,
        ?string $host = null,
        ?string $apiKey = null,
        string $logLevel = Types::INFO
    ) {
        $this->logger = new LogBullLogger($projectId, $host, $apiKey, $logLevel);
    }

    /**
     * System is unusable.
     * 
     * @param array<string, mixed> $context
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical((string)$message, $context);
    }

    /**
     * Action must be taken immediately.
     * 
     * @param array<string, mixed> $context
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical((string)$message, $context);
    }

    /**
     * Critical conditions.
     * 
     * @param array<string, mixed> $context
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical((string)$message, $context);
    }

    /**
     * Runtime errors that do not require immediate action.
     * 
     * @param array<string, mixed> $context
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->logger->error((string)$message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     * 
     * @param array<string, mixed> $context
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->logger->warning((string)$message, $context);
    }

    /**
     * Normal but significant events.
     * 
     * @param array<string, mixed> $context
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->logger->info((string)$message, $context);
    }

    /**
     * Interesting events.
     * 
     * @param array<string, mixed> $context
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->logger->info((string)$message, $context);
    }

    /**
     * Detailed debug information.
     * 
     * @param array<string, mixed> $context
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->logger->debug((string)$message, $context);
    }

    /**
     * Logs with an arbitrary level.
     * 
     * @param array<string, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $logbullLevel = $this->convertPSR3Level($level);
        
        match ($logbullLevel) {
            Types::DEBUG => $this->logger->debug((string)$message, $context),
            Types::INFO => $this->logger->info((string)$message, $context),
            Types::WARNING => $this->logger->warning((string)$message, $context),
            Types::ERROR => $this->logger->error((string)$message, $context),
            Types::CRITICAL => $this->logger->critical((string)$message, $context),
            default => $this->logger->info((string)$message, $context),
        };
    }

    /**
     * Flush pending logs
     */
    public function flush(): void
    {
        $this->logger->flush();
    }

    /**
     * Shutdown and send remaining logs
     */
    public function shutdown(): void
    {
        $this->logger->shutdown();
    }

    /**
     * Convert PSR-3 log level to LogBull log level
     */
    private function convertPSR3Level(mixed $level): string
    {
        if (!is_string($level)) {
            return Types::INFO;
        }

        return match (strtolower($level)) {
            LogLevel::DEBUG => Types::DEBUG,
            LogLevel::INFO, LogLevel::NOTICE => Types::INFO,
            LogLevel::WARNING => Types::WARNING,
            LogLevel::ERROR => Types::ERROR,
            LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY => Types::CRITICAL,
            default => Types::INFO,
        };
    }
}

