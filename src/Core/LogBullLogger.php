<?php

declare(strict_types=1);

namespace LogBull\Core;

use LogBull\Internal\Validation;
use LogBull\Internal\Formatting;

/**
 * LogBull standalone logger
 * Provides a simple logging interface with context management
 */
class LogBullLogger
{
    private ?string $projectId;
    private ?string $host;
    private ?string $apiKey;
    private string $minLevel;
    private int $batchSize;
    private ?float $flushInterval;
    
    /** @var array<string, mixed> */
    private array $context;
    
    private ?Sender $sender;

    /**
     * @param array<string, mixed> $context
     * @param int $batchSize Number of queued logs that triggers a send
     * @param float|null $flushInterval Seconds after which queued logs are sent even if the batch
     *                                  is not full (null disables time-based sending)
     */
    public function __construct(
        ?string $projectId = null,
        ?string $host = null,
        ?string $apiKey = null,
        string $logLevel = Types::INFO,
        array $context = [],
        int $batchSize = Sender::DEFAULT_BATCH_SIZE,
        ?float $flushInterval = null
    ) {
        // Trim configuration
        $this->projectId = $projectId !== null ? trim($projectId) : null;
        $this->host = $host !== null ? trim($host) : null;
        $this->apiKey = $apiKey !== null ? trim($apiKey) : null;

        // Normalize and validate log level
        $this->minLevel = Types::normalizeLevel($logLevel);
        if (!Types::isValidLevel($this->minLevel)) {
            throw new \InvalidArgumentException("Invalid log level: $logLevel");
        }

        $this->context = $context;
        $this->batchSize = $batchSize;
        $this->flushInterval = $flushInterval;

        // Check if credentials are provided
        if ($this->projectId === null || $this->projectId === '' || 
            $this->host === null || $this->host === '') {
            // Console-only mode: no credentials provided
            echo "LogBull: No credentials provided. Running in console-only mode. " .
                 "Logs will only be printed to the console and not sent to LogBull server.\n";
            $this->sender = null;
            return;
        }

        // Validate configuration
        Validation::validateProjectId($this->projectId);
        Validation::validateHostUrl($this->host);

        if ($this->apiKey !== null && $this->apiKey !== '') {
            Validation::validateApiKey($this->apiKey);
        }

        $this->sender = new Sender(
            $this->projectId,
            $this->host,
            $this->apiKey,
            $this->batchSize,
            $this->flushInterval
        );
    }

    /**
     * Log debug message
     * 
     * @param array<string, mixed>|null $fields
     */
    public function debug(string $message, ?array $fields = null): void
    {
        $this->log(Types::DEBUG, $message, $fields ?? []);
    }

    /**
     * Log info message
     * 
     * @param array<string, mixed>|null $fields
     */
    public function info(string $message, ?array $fields = null): void
    {
        $this->log(Types::INFO, $message, $fields ?? []);
    }

    /**
     * Log warning message
     * 
     * @param array<string, mixed>|null $fields
     */
    public function warning(string $message, ?array $fields = null): void
    {
        $this->log(Types::WARNING, $message, $fields ?? []);
    }

    /**
     * Log error message
     * 
     * @param array<string, mixed>|null $fields
     */
    public function error(string $message, ?array $fields = null): void
    {
        $this->log(Types::ERROR, $message, $fields ?? []);
    }

    /**
     * Log critical message
     * 
     * @param array<string, mixed>|null $fields
     */
    public function critical(string $message, ?array $fields = null): void
    {
        $this->log(Types::CRITICAL, $message, $fields ?? []);
    }

    /**
     * Create a new logger with additional context
     * The new logger shares the same sender
     * 
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        $newLogger = new self(
            $this->projectId,
            $this->host,
            $this->apiKey,
            $this->minLevel,
            Formatting::mergeFields($this->context, $context),
            $this->batchSize,
            $this->flushInterval
        );
        
        // Share the same sender instance
        $newLogger->sender = $this->sender;
        
        return $newLogger;
    }

    /**
     * Force send all queued logs immediately
     */
    public function flush(): void
    {
        if ($this->sender !== null) {
            $this->sender->flush();
        }
    }

    /**
     * Stop processing and send remaining logs
     */
    public function shutdown(): void
    {
        if ($this->sender !== null) {
            $this->sender->shutdown();
        }
    }

    /**
     * Internal log method
     * 
     * @param array<string, mixed> $fields
     */
    private function log(string $level, string $message, array $fields): void
    {
        // Check level filtering
        if (Types::getPriority($level) < Types::getPriority($this->minLevel)) {
            return;
        }

        // Validate inputs
        try {
            Validation::validateLogMessage($message);
            Validation::validateLogFields($fields);
        } catch (\InvalidArgumentException $e) {
            fwrite(STDERR, "LogBull: invalid log: {$e->getMessage()}\n");
            return;
        }

        // Merge context and fields
        $mergedFields = Formatting::mergeFields($this->context, $fields);

        // Create log entry
        $entry = [
            'level' => $level,
            'message' => Formatting::formatMessage($message),
            'timestamp' => Timestamp::generateUniqueTimestamp(),
            'fields' => Formatting::ensureFields($mergedFields),
        ];

        // Print to console
        $this->printToConsole($entry);

        // Only send to LogBull server if not in console-only mode
        if ($this->sender !== null) {
            $this->sender->addLog($entry);
        }
    }

    /**
     * Print log entry to console
     * 
     * @param array<string, mixed> $entry
     */
    private function printToConsole(array $entry): void
    {
        $output = "[{$entry['timestamp']}] [{$entry['level']}] {$entry['message']}";

        // Add fields if present
        if (!empty($entry['fields'])) {
            $fieldStrings = [];
            foreach ($entry['fields'] as $key => $value) {
                $valueStr = is_scalar($value) ? (string)$value : json_encode($value);
                $fieldStrings[] = "$key=$valueStr";
            }
            $output .= ' (' . implode(', ', $fieldStrings) . ')';
        }

        $output .= "\n";

        // Use appropriate stream
        if ($entry['level'] === Types::ERROR || $entry['level'] === Types::CRITICAL) {
            fwrite(STDERR, $output);
        } else {
            echo $output;
        }
    }
}

