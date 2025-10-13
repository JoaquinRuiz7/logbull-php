<?php

declare(strict_types=1);

namespace LogBull\Internal;

use InvalidArgumentException;

/**
 * Validation utilities for LogBull inputs
 */
class Validation
{
    private const UUID_PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
    private const API_KEY_PATTERN = '/^[a-zA-Z0-9_\-.]{10,}$/';
    
    private const MAX_MESSAGE_LENGTH = 10_000;
    private const MAX_FIELDS_COUNT = 100;
    private const MAX_FIELD_KEY_LENGTH = 100;

    /**
     * Validate project ID (must be UUID format)
     * 
     * @throws InvalidArgumentException
     */
    public static function validateProjectId(string $projectId): void
    {
        $trimmed = trim($projectId);
        
        if ($trimmed === '') {
            throw new InvalidArgumentException('Project ID cannot be empty');
        }

        if (!preg_match(self::UUID_PATTERN, $trimmed)) {
            throw new InvalidArgumentException(
                "Invalid project ID format '$projectId'. Must be a valid UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
            );
        }
    }

    /**
     * Validate host URL (must be http or https)
     * 
     * @throws InvalidArgumentException
     */
    public static function validateHostUrl(string $host): void
    {
        $trimmed = trim($host);
        
        if ($trimmed === '') {
            throw new InvalidArgumentException('Host URL cannot be empty');
        }

        $parsed = parse_url($trimmed);
        
        if ($parsed === false) {
            throw new InvalidArgumentException("Invalid host URL format: $host");
        }

        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'], true)) {
            $scheme = $parsed['scheme'] ?? 'none';
            throw new InvalidArgumentException("Host URL must use http or https scheme, got: $scheme");
        }

        if (!isset($parsed['host']) || $parsed['host'] === '') {
            throw new InvalidArgumentException('Host URL must have a host component');
        }
    }

    /**
     * Validate API key (min 10 chars, alphanumeric + _-.)
     * 
     * @throws InvalidArgumentException
     */
    public static function validateApiKey(?string $apiKey): void
    {
        if ($apiKey === null) {
            return;
        }

        $trimmed = trim($apiKey);
        
        if (strlen($trimmed) < 10) {
            throw new InvalidArgumentException('API key must be at least 10 characters long');
        }

        if (!preg_match(self::API_KEY_PATTERN, $trimmed)) {
            throw new InvalidArgumentException(
                'Invalid API key format. API key must contain only alphanumeric characters, underscores, hyphens, and dots'
            );
        }
    }

    /**
     * Validate log message (non-empty, max length)
     * 
     * @throws InvalidArgumentException
     */
    public static function validateLogMessage(string $message): void
    {
        $trimmed = trim($message);
        
        if ($trimmed === '') {
            throw new InvalidArgumentException('Log message cannot be empty');
        }

        if (strlen($trimmed) > self::MAX_MESSAGE_LENGTH) {
            $length = strlen($trimmed);
            throw new InvalidArgumentException(
                "Log message too long ($length chars). Maximum allowed: " . self::MAX_MESSAGE_LENGTH
            );
        }
    }

    /**
     * Validate log fields (max count, key length)
     * 
     * @param array<string, mixed>|null $fields
     * @throws InvalidArgumentException
     */
    public static function validateLogFields(?array $fields): void
    {
        if ($fields === null || $fields === []) {
            return;
        }

        if (count($fields) > self::MAX_FIELDS_COUNT) {
            $count = count($fields);
            throw new InvalidArgumentException(
                "Too many fields ($count). Maximum allowed: " . self::MAX_FIELDS_COUNT
            );
        }

        foreach (array_keys($fields) as $key) {
            $trimmedKey = trim((string)$key);
            
            if ($trimmedKey === '') {
                throw new InvalidArgumentException('Field key cannot be empty');
            }

            if (strlen($trimmedKey) > self::MAX_FIELD_KEY_LENGTH) {
                $length = strlen($trimmedKey);
                throw new InvalidArgumentException(
                    "Field key too long ($length chars). Maximum: " . self::MAX_FIELD_KEY_LENGTH
                );
            }
        }
    }
}

