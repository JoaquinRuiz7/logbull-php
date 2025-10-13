<?php

declare(strict_types=1);

namespace LogBull\Internal;

/**
 * Formatting utilities for log messages and fields
 */
class Formatting
{
    private const MAX_MESSAGE_LENGTH = 10_000;

    /**
     * Format and truncate log message
     */
    public static function formatMessage(string $message): string
    {
        $trimmed = trim($message);

        if (strlen($trimmed) > self::MAX_MESSAGE_LENGTH) {
            return substr($trimmed, 0, self::MAX_MESSAGE_LENGTH - 3) . '...';
        }

        return $trimmed;
    }

    /**
     * Ensure fields are JSON-serializable and sanitize keys
     * 
     * @param array<string, mixed>|null $fields
     * @return array<string, mixed>
     */
    public static function ensureFields(?array $fields): array
    {
        if ($fields === null || $fields === []) {
            return [];
        }

        $formatted = [];

        foreach ($fields as $key => $value) {
            $trimmedKey = trim((string)$key);

            if ($trimmedKey === '') {
                continue;
            }

            if (self::isJsonSerializable($value)) {
                $formatted[$trimmedKey] = $value;
            } else {
                $formatted[$trimmedKey] = self::convertToString($value);
            }
        }

        return $formatted;
    }

    /**
     * Merge two field arrays, with additional overriding base
     * 
     * @param array<string, mixed> $base
     * @param array<string, mixed> $additional
     * @return array<string, mixed>
     */
    public static function mergeFields(array $base, array $additional): array
    {
        $result = self::ensureFields($base);
        $additionalFormatted = self::ensureFields($additional);

        foreach ($additionalFormatted as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Check if a value is JSON serializable
     */
    private static function isJsonSerializable(mixed $value): bool
    {
        try {
            json_encode($value, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    /**
     * Convert non-serializable value to string
     */
    private static function convertToString(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Fallback to string conversion
            if (is_object($value) && method_exists($value, '__toString')) {
                return (string)$value;
            }
            
            if (is_resource($value)) {
                return 'resource(' . get_resource_type($value) . ')';
            }

            return var_export($value, true);
        }
    }
}

