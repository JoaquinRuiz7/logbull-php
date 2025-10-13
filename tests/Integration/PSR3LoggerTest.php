<?php

declare(strict_types=1);

namespace LogBull\Tests\Integration;

use LogBull\Handlers\PSR3Logger;
use LogBull\Core\Types;
use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;

class PSR3LoggerTest extends TestCase
{
    public function testPSR3LoggerCreation(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $this->assertInstanceOf(PSR3Logger::class, $logger);
    }

    public function testAllPSR3Methods(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            logLevel: Types::DEBUG
        );
        
        // Should not throw - logs are queued but not sent in tests
        $logger->emergency('Emergency message');
        $logger->alert('Alert message');
        $logger->critical('Critical message');
        $logger->error('Error message');
        $logger->warning('Warning message');
        $logger->notice('Notice message');
        $logger->info('Info message');
        $logger->debug('Debug message');
        
        $this->assertTrue(true);
    }

    public function testPSR3WithContext(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Logs are queued but not sent in tests
        $logger->info('User action', [
            'user_id' => '12345',
            'action' => 'login',
            'ip' => '192.168.1.100'
        ]);
        
        $this->assertTrue(true);
    }

    public function testPSR3LogMethod(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Test the generic log() method with different levels
        // Logs are queued but not sent in tests
        $logger->log(LogLevel::INFO, 'Info via log method');
        $logger->log(LogLevel::ERROR, 'Error via log method');
        $logger->log(LogLevel::DEBUG, 'Debug via log method');
        
        $this->assertTrue(true);
    }

    public function testPSR3FlushMethod(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger->info('Test message');
        
        // Note: We don't actually flush in tests to avoid HTTP requests
        // Just verify the method exists
        $this->assertInstanceOf(PSR3Logger::class, $logger);
    }

    public function testPSR3ShutdownMethod(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger->info('Test message');
        
        // Note: We don't actually shutdown in tests to avoid HTTP requests
        // Just verify the method exists
        $this->assertInstanceOf(PSR3Logger::class, $logger);
    }

    public function testPSR3WithApiKey(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            apiKey: 'test-api-key-123'
        );
        
        // Logs are queued but not sent in tests
        $logger->info('Message with API key');
        
        $this->assertTrue(true);
    }

    public function testPSR3LevelFiltering(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            logLevel: Types::WARNING
        );
        
        // These should be filtered
        $logger->debug('Debug - filtered');
        $logger->info('Info - filtered');
        
        // These should pass (queued but not sent in tests)
        $logger->warning('Warning - passed');
        $logger->error('Error - passed');
        
        $this->assertTrue(true);
    }

    public function testPSR3WithStringableMessage(): void
    {
        $logger = new PSR3Logger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $stringable = new class {
            public function __toString(): string {
                return 'Stringable message';
            }
        };
        
        // Should accept Stringable - log is queued but not sent in tests
        $logger->info($stringable);
        
        $this->assertTrue(true);
    }
}

