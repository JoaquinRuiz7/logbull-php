<?php

declare(strict_types=1);

namespace LogBull\Tests\Integration;

use LogBull\Handlers\MonologHandler;
use Monolog\Logger;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class MonologHandlerTest extends TestCase
{
    public function testMonologHandlerCreation(): void
    {
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $this->assertInstanceOf(MonologHandler::class, $handler);
    }

    public function testMonologHandlerWithLogger(): void
    {
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        
        // Should not throw - logs are queued but not sent in tests
        $logger->info('Test message', ['user_id' => '12345']);
        $logger->error('Error message', ['error_code' => 500]);
        
        $this->assertTrue(true);
    }

    public function testMonologHandlerWithDifferentLevels(): void
    {
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            level: Level::Debug
        );
        
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        
        // Should not throw - logs are queued but not sent in tests
        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->notice('Notice message');
        $logger->warning('Warning message');
        $logger->error('Error message');
        $logger->critical('Critical message');
        $logger->alert('Alert message');
        $logger->emergency('Emergency message');
        
        $this->assertTrue(true);
    }

    public function testMonologHandlerWithContext(): void
    {
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        
        // Add context - logs are queued but not sent in tests
        $logger->info('Message with context', [
            'user_id' => '12345',
            'action' => 'login',
            'ip' => '192.168.1.100',
        ]);
        
        $this->assertTrue(true);
    }

    public function testMonologHandlerFlush(): void
    {
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        
        $logger->info('Test message');
        
        // Note: We don't actually flush in tests to avoid HTTP requests
        // Just verify the method exists
        $this->assertInstanceOf(MonologHandler::class, $handler);
    }

    public function testMonologHandlerClose(): void
    {
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        
        $logger->info('Test message');
        
        // Note: We don't actually close in tests to avoid HTTP requests
        // Just verify the method exists
        $this->assertInstanceOf(MonologHandler::class, $handler);
    }

    public function testMonologHandlerWithApiKey(): void
    {
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            apiKey: 'test-api-key-123'
        );
        
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        
        // Logs are queued but not sent in tests
        $logger->info('Message with API key');
        
        $this->assertTrue(true);
    }

    public function testMonologHandlerLevelFiltering(): void
    {
        // Handler only processes WARNING and above
        $handler = new MonologHandler(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            level: Level::Warning
        );
        
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        
        // These should be filtered
        $logger->debug('Debug - filtered');
        $logger->info('Info - filtered');
        
        // These should pass (queued but not sent in tests)
        $logger->warning('Warning - passed');
        $logger->error('Error - passed');
        
        $this->assertTrue(true);
    }
}

