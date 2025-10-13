<?php

declare(strict_types=1);

namespace LogBull\Tests\Integration;

use LogBull\Handlers\LaravelHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LaravelHandlerTest extends TestCase
{
    public function testHandlerReturnsMonologLogger(): void
    {
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'http://localhost:4005',
            'level' => 'debug',
        ];
        
        $logger = $handler($config);
        
        $this->assertInstanceOf(Logger::class, $logger);
    }
    
    public function testHandlerWithValidConfiguration(): void
    {
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'http://localhost:4005',
            'api_key' => 'test-api-key-1234567890',
            'level' => 'info',
        ];
        
        $logger = $handler($config);
        
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals('logbull', $logger->getName());
    }
    
    public function testHandlerWithoutOptionalApiKey(): void
    {
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'http://localhost:4005',
            'level' => 'warning',
        ];
        
        $logger = $handler($config);
        
        $this->assertInstanceOf(Logger::class, $logger);
    }
    
    public function testHandlerWithDifferentLogLevels(): void
    {
        $handler = new LaravelHandler();
        
        $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        
        foreach ($levels as $level) {
            $config = [
                'project_id' => '12345678-1234-1234-1234-123456789012',
                'host' => 'http://localhost:4005',
                'level' => $level,
            ];
            
            $logger = $handler($config);
            
            $this->assertInstanceOf(Logger::class, $logger);
        }
    }
    
    public function testHandlerWithDefaultLevel(): void
    {
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'http://localhost:4005',
        ];
        
        $logger = $handler($config);
        
        $this->assertInstanceOf(Logger::class, $logger);
    }
    
    public function testHandlerWithInvalidProjectIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $handler = new LaravelHandler();
        $config = [
            'project_id' => 'invalid-uuid',
            'host' => 'http://localhost:4005',
        ];
        
        $handler($config);
    }
    
    public function testHandlerWithInvalidHostThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'not-a-valid-url',
        ];
        
        $handler($config);
    }
    
    public function testHandlerWithEmptyConfigThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $handler = new LaravelHandler();
        $config = [];
        
        $handler($config);
    }
    
    public function testHandlerCanLog(): void
    {
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'http://localhost:4005',
            'level' => 'debug',
        ];
        
        $logger = $handler($config);
        
        // Test that we can call log methods without errors
        $logger->info('Test message', ['key' => 'value']);
        $logger->debug('Debug message');
        $logger->error('Error message', ['error' => 'test']);
        
        $this->assertTrue(true); // If we got here without exceptions, it works
    }
}

