<?php

declare(strict_types=1);

namespace LogBull\Handlers;

use Monolog\Logger;
use Monolog\Level;

/**
 * Laravel custom log channel handler for LogBull
 * 
 * This handler integrates LogBull with Laravel's logging system.
 * It can be used as a custom channel in config/logging.php.
 */
class LaravelHandler
{
    /**
     * Create a custom Monolog driver for Laravel
     * 
     * @param array<string, mixed> $config
     * @return Logger
     */
    public function __invoke(array $config): Logger
    {
        // Extract configuration values
        $projectId = $config['project_id'] ?? '';
        $host = $config['host'] ?? '';
        $apiKey = $config['api_key'] ?? null;
        $level = $config['level'] ?? 'info';
        
        // Convert empty string API key to null
        if ($apiKey === '' || (is_string($apiKey) && strlen($apiKey) < 10)) {
            $apiKey = null;
        }
        
        // Convert string level to Monolog Level enum
        $monologLevel = $this->parseLevel($level);
        
        // Create MonologHandler instance
        $handler = new MonologHandler($projectId, $host, $apiKey, $monologLevel);
        
        // Create and return Monolog Logger
        $logger = new Logger('logbull');
        $logger->pushHandler($handler);
        
        return $logger;
    }
    
    /**
     * Parse the string log level to Monolog Level
     */
    private function parseLevel(string|int $level): Level
    {
        if ($level instanceof Level) {
            return $level;
        }
        
        if (is_int($level)) {
            return Level::fromValue($level);
        }
        
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning', 'warn' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };
    }
}

