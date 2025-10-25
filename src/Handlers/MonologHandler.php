<?php

declare(strict_types=1);

namespace LogBull\Handlers;

use LogBull\Core\Sender;
use LogBull\Core\Timestamp;
use LogBull\Core\Types;
use LogBull\Internal\Formatting;
use LogBull\Internal\Validation;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Monolog handler for LogBull
 */
class MonologHandler extends AbstractProcessingHandler
{
    private ?Sender $sender;

    /**
     * @param int|string|Level $level The minimum logging level at which this handler will be triggered
     */
    public function __construct(
        ?string $projectId = null,
        ?string $host = null,
        ?string $apiKey = null,
        int|string|Level $level = Level::Info
    ) {
        parent::__construct($level);

        // Trim configuration
        $projectId = $projectId !== null ? trim($projectId) : null;
        $host = $host !== null ? trim($host) : null;
        $apiKey = $apiKey !== null ? trim($apiKey) : null;

        // Check if credentials are provided
        if ($projectId === null || $projectId === '' || 
            $host === null || $host === '') {
            // No credentials: do nothing (Monolog will print)
            echo "LogBull: No credentials provided for MonologHandler. " .
                 "Handler is disabled. Logs will not be sent to LogBull server.\n";
            $this->sender = null;
            return;
        }

        // Validate configuration
        Validation::validateProjectId($projectId);
        Validation::validateHostUrl($host);

        if ($apiKey !== null && $apiKey !== '') {
            Validation::validateApiKey($apiKey);
        }

        $this->sender = new Sender($projectId, $host, $apiKey);
    }

    /**
     * Write the record down to the log of the implementing handler
     */
    protected function write(LogRecord $record): void
    {
        // If handler is disabled, do nothing
        if ($this->sender === null) {
            return;
        }

        try {
            // Convert Monolog level to LogBull level
            $level = $this->convertMonologLevel($record->level);

            // Extract message
            $message = $record->message;

            // Extract fields from context and extra
            $fields = array_merge($record->context, $record->extra);

            // Create log entry
            $entry = [
                'level' => $level,
                'message' => Formatting::formatMessage($message),
                'timestamp' => Timestamp::generateUniqueTimestamp(),
                'fields' => Formatting::ensureFields($fields),
            ];

            // Send to LogBull
            $this->sender->addLog($entry);
        } catch (\Throwable $e) {
            fwrite(STDERR, "LogBull Monolog Handler error: {$e->getMessage()}\n");
        }
    }

    /**
     * Flush pending logs
     */
    public function flush(): void
    {
        if ($this->sender !== null) {
            $this->sender->flush();
        }
    }

    /**
     * Close the handler
     */
    public function close(): void
    {
        if ($this->sender !== null) {
            $this->sender->shutdown();
        }
        parent::close();
    }

    /**
     * Convert Monolog log level to LogBull log level
     */
    private function convertMonologLevel(Level $level): string
    {
        return match ($level) {
            Level::Debug => Types::DEBUG,
            Level::Info, Level::Notice => Types::INFO,
            Level::Warning => Types::WARNING,
            Level::Error => Types::ERROR,
            Level::Critical, Level::Alert, Level::Emergency => Types::CRITICAL,
        };
    }
}

