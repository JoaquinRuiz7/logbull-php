<?php

declare(strict_types=1);

namespace LogBull\Core;

/**
 * Timestamp generator with monotonic guarantees
 * 
 * Note: PHP uses microsecond precision (6 decimal places) rather than nanosecond precision (9 decimal places)
 * due to DateTime limitations. This means timestamps are in the format: 2025-01-15T10:30:45.123456Z
 * where the fractional seconds have 6 digits instead of 9.
 */
class Timestamp
{
    private static int $lastTimestampUs = 0;

    /**
     * Generate a unique RFC3339 timestamp with microsecond precision (UTC time)
     * 
     * Ensures monotonic ordering by incrementing if the same timestamp is generated.
     * This prevents duplicate timestamps even when multiple logs are created within the same microsecond.
     */
    public static function generateUniqueTimestamp(): string
    {
        // Get current time in microseconds
        $currentUs = (int)(microtime(true) * 1_000_000);

        // Ensure monotonic ordering (each timestamp must be unique and increasing)
        if ($currentUs <= self::$lastTimestampUs) {
            $currentUs = self::$lastTimestampUs + 1;
        }
        self::$lastTimestampUs = $currentUs;

        return self::formatTimestamp($currentUs);
    }

    /**
     * Format microsecond timestamp to RFC3339 format with microsecond precision
     */
    private static function formatTimestamp(int $timestampUs): string
    {
        $seconds = intdiv($timestampUs, 1_000_000);
        $microseconds = $timestampUs % 1_000_000;

        // Create DateTime from seconds
        $dateTime = new \DateTime('@' . $seconds, new \DateTimeZone('UTC'));
        
        // Format with microseconds: Y-m-d\TH:i:s.uZ
        // We need to manually add microseconds since DateTime::format('u') uses current microseconds, not our calculated ones
        return $dateTime->format('Y-m-d\TH:i:s') . '.' . str_pad((string)$microseconds, 6, '0', STR_PAD_LEFT) . 'Z';
    }

    /**
     * Reset the timestamp tracker (useful for testing)
     */
    public static function reset(): void
    {
        self::$lastTimestampUs = 0;
    }
}

