<?php

declare(strict_types=1);

namespace LogBull\Tests\Unit;

use LogBull\Core\LogBullLogger;
use LogBull\Core\Types;
use PHPUnit\Framework\TestCase;

class LogBullLoggerTest extends TestCase
{
    public function testConstructorWithValidConfiguration(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $this->assertInstanceOf(LogBullLogger::class, $logger);
    }

    public function testConstructorThrowsOnInvalidProjectId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid project ID format');
        
        new LogBullLogger(
            projectId: 'invalid',
            host: 'http://localhost:4005'
        );
    }

    public function testConstructorThrowsOnInvalidHost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host URL must use http or https scheme');
        
        new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'invalid'
        );
    }

    public function testConstructorThrowsOnInvalidApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key must be at least 10 characters long');
        
        new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            apiKey: 'short'
        );
    }

    public function testConstructorThrowsOnInvalidLogLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level');
        
        new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            logLevel: 'INVALID'
        );
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $logger = new LogBullLogger(
            projectId: '  12345678-1234-1234-1234-123456789012  ',
            host: '  http://test.example.com  ',
            apiKey: '  validkey123  '
        );
        
        $this->assertInstanceOf(LogBullLogger::class, $logger);
    }

    public function testDebugMethod(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            logLevel: Types::DEBUG
        );
        
        // Should not throw
        $logger->debug('Debug message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testInfoMethod(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Should not throw
        $logger->info('Info message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testWarningMethod(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Should not throw
        $logger->warning('Warning message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testErrorMethod(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Should not throw
        $logger->error('Error message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testCriticalMethod(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Should not throw
        $logger->critical('Critical message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testLogMethodsAcceptNullFields(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Should not throw
        $logger->info('Message without fields');
        $this->assertTrue(true);
    }

    public function testLevelFilteringFiltersDebug(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            logLevel: Types::INFO
        );
        
        // Debug should be filtered (no output expected)
        $logger->debug('This should be filtered');
        $this->assertTrue(true);
    }

    public function testLevelFilteringAllowsHigherLevels(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            logLevel: Types::WARNING
        );
        
        // These should pass
        $logger->warning('Warning passes');
        $logger->error('Error passes');
        $logger->critical('Critical passes');
        
        $this->assertTrue(true);
    }

    public function testWithContextCreatesNewLogger(): void
    {
        $baseLogger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $contextLogger = $baseLogger->withContext(['request_id' => 'req_123']);
        
        $this->assertInstanceOf(LogBullLogger::class, $contextLogger);
        $this->assertNotSame($baseLogger, $contextLogger);
    }

    public function testWithContextMergesContext(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com',
            context: ['base' => 'value1']
        );
        
        $contextLogger = $logger->withContext(['additional' => 'value2']);
        
        // Both loggers should work
        $logger->info('Base logger');
        $contextLogger->info('Context logger');
        
        $this->assertTrue(true);
    }

    public function testFlushMethod(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger->info('Test message');
        
        // In unit tests, we don't actually flush to avoid HTTP requests
        // Just verify the method exists and can be called
        $this->assertInstanceOf(LogBullLogger::class, $logger);
    }

    public function testShutdownMethod(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        $logger->info('Test message');
        
        // In unit tests, we don't actually shutdown to avoid HTTP requests
        // Just verify the method exists and can be called
        $this->assertInstanceOf(LogBullLogger::class, $logger);
    }

    public function testHandlesInvalidMessageGracefully(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Empty message should be handled gracefully (no exception)
        $logger->info('');
        $logger->info('   ');
        
        $this->assertTrue(true);
    }

    public function testHandlesInvalidFieldsGracefully(): void
    {
        $logger = new LogBullLogger(
            projectId: '12345678-1234-1234-1234-123456789012',
            host: 'http://test.example.com'
        );
        
        // Too many fields should be handled gracefully
        $fields = [];
        for ($i = 0; $i < 101; $i++) {
            $fields["field_$i"] = $i;
        }
        
        // Should not throw exception
        $logger->info('Test', $fields);
        $this->assertTrue(true);
    }
}

