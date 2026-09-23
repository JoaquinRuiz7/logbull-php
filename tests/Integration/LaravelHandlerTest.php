<?php

declare(strict_types=1);

namespace LogBull\Tests\Integration;

use LogBull\Core\Sender;
use LogBull\Handlers\LaravelHandler;
use LogBull\Handlers\MonologHandler;
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

    public function testHandlerPassesBatchSizeAndFlushInterval(): void
    {
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'http://localhost:4005',
            'batch_size' => '50',
            'flush_interval' => '2.5',
        ];

        $sender = $this->getSender($handler($config));

        $this->assertSame(50, (new \ReflectionProperty(Sender::class, 'batchSize'))->getValue($sender));
        $this->assertSame(2.5, (new \ReflectionProperty(Sender::class, 'flushInterval'))->getValue($sender));
    }

    public function testHandlerUsesDefaultsForEmptyBatchSizeAndFlushInterval(): void
    {
        $handler = new LaravelHandler();
        $config = [
            'project_id' => '12345678-1234-1234-1234-123456789012',
            'host' => 'http://localhost:4005',
            'batch_size' => '',
            'flush_interval' => null,
        ];

        $sender = $this->getSender($handler($config));

        $this->assertSame(Sender::DEFAULT_BATCH_SIZE, (new \ReflectionProperty(Sender::class, 'batchSize'))->getValue($sender));
        $this->assertNull((new \ReflectionProperty(Sender::class, 'flushInterval'))->getValue($sender));
    }

    private function getSender(Logger $logger): Sender
    {
        $monologHandler = $logger->getHandlers()[0];

        return (new \ReflectionProperty(MonologHandler::class, 'sender'))->getValue($monologHandler);
    }
}
