<?php

declare(strict_types=1);

namespace LogBull\Core;

/**
 * Log level types and utilities
 */
class Types
{
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    private const LEVEL_PRIORITY = [
        self::DEBUG => 10,
        self::INFO => 20,
        self::WARNING => 30,
        self::ERROR => 40,
        self::CRITICAL => 50,
    ];

    /**
     * Get the priority value for a log level
     */
    public static function getPriority(string $level): int
    {
        return self::LEVEL_PRIORITY[$level] ?? 0;
    }

    /**
     * Validate if a log level is valid
     */
    public static function isValidLevel(string $level): bool
    {
        return isset(self::LEVEL_PRIORITY[$level]);
    }

    /**
     * Get all valid log levels
     * 
     * @return array<string>
     */
    public static function getValidLevels(): array
    {
        return array_keys(self::LEVEL_PRIORITY);
    }

    /**
     * Normalize log level (handle common variations)
     */
    public static function normalizeLevel(string $level): string
    {
        $upper = strtoupper($level);
        
        // Handle common variations
        return match ($upper) {
            'WARN' => self::WARNING,
            'FATAL' => self::CRITICAL,
            default => $upper,
        };
    }
}

