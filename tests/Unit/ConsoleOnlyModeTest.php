<?php

declare(strict_types=1);

namespace LogBull\Tests\Unit;

use LogBull\Core\LogBullLogger;
use LogBull\Core\Types;
use LogBull\Handlers\MonologHandler;
use LogBull\Handlers\PSR3Logger;
use PHPUnit\Framework\TestCase;

/**
 * Tests for console-only mode (no credentials)
 */
class ConsoleOnlyModeTest extends TestCase
{
    public function testLoggerWithoutCredentials(): void
    {
        // Capture output
        ob_start();
        $logger = new LogBullLogger();
        $output = ob_get_clean();

        $this->assertNotNull($logger);
        $this->assertStringContainsString('No credentials provided', $output);
    }

    public function testLoggerWithOnlyProjectId(): void
    {
        ob_start();
        $logger = new LogBullLogger('12345678-1234-1234-1234-123456789012');
        $output = ob_get_clean();

        $this->assertNotNull($logger);
        $this->assertStringContainsString('No credentials provided', $output);
    }

    public function testLoggerWithOnlyHost(): void
    {
        ob_start();
        $logger = new LogBullLogger(null, 'http://localhost:4005');
        $output = ob_get_clean();

        $this->assertNotNull($logger);
        $this->assertStringContainsString('No credentials provided', $output);
    }

    public function testLoggerWithEmptyStrings(): void
    {
        ob_start();
        $logger = new LogBullLogger('', '');
        $output = ob_get_clean();

        $this->assertNotNull($logger);
        $this->assertStringContainsString('No credentials provided', $output);
    }

    public function testConsoleOnlyLogging(): void
    {
        ob_start();
        $logger = new LogBullLogger();
        ob_get_clean(); // Clear init message

        ob_start();
        $logger->info('Test message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test message', $output);
    }

    public function testConsoleOnlyWithFields(): void
    {
        ob_start();
        $logger = new LogBullLogger();
        ob_get_clean();

        ob_start();
        $logger->info('Test message', ['key' => 'value']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Test message', $output);
        $this->assertStringContainsString('key=value', $output);
    }

    public function testConsoleOnlyWithContext(): void
    {
        ob_start();
        $logger = new LogBullLogger();
        ob_get_clean();

        $contextLogger = $logger->withContext(['app' => 'test']);
        $this->assertNotNull($contextLogger);

        ob_start();
        $contextLogger->info('Test with context');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test with context', $output);
        $this->assertStringContainsString('app=test', $output);
    }

    public function testConsoleOnlyFlushDoesNotThrow(): void
    {
        ob_start();
        $logger = new LogBullLogger();
        ob_get_clean();

        $this->expectNotToPerformAssertions();
        $logger->flush();
    }

    public function testConsoleOnlyShutdownDoesNotThrow(): void
    {
        ob_start();
        $logger = new LogBullLogger();
        ob_get_clean();

        $this->expectNotToPerformAssertions();
        $logger->shutdown();
    }

    public function testConsoleOnlyLevelFiltering(): void
    {
        ob_start();
        $logger = new LogBullLogger(null, null, null, Types::WARNING);
        ob_get_clean();

        ob_start();
        $logger->debug('Should be filtered');
        $logger->info('Should be filtered');
        $logger->warning('Should pass');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('Should be filtered', $output);
        $this->assertStringContainsString('Should pass', $output);
    }

    public function testLoggerWithFullCredentialsNotConsoleOnly(): void
    {
        ob_start();
        $logger = new LogBullLogger(
            '12345678-1234-1234-1234-123456789012',
            'http://localhost:4005'
        );
        $output = ob_get_clean();

        $this->assertNotNull($logger);
        // Should NOT show the console-only message
        $this->assertStringNotContainsString('No credentials provided', $output);
    }

    public function testMonologHandlerWithoutCredentials(): void
    {
        ob_start();
        $handler = new MonologHandler();
        $output = ob_get_clean();

        $this->assertNotNull($handler);
        $this->assertStringContainsString('No credentials provided', $output);
    }

    public function testMonologHandlerFlushDoesNotThrowWhenDisabled(): void
    {
        ob_start();
        $handler = new MonologHandler();
        ob_get_clean();

        $this->expectNotToPerformAssertions();
        $handler->flush();
    }

    public function testMonologHandlerCloseDoesNotThrowWhenDisabled(): void
    {
        ob_start();
        $handler = new MonologHandler();
        ob_get_clean();

        $this->expectNotToPerformAssertions();
        $handler->close();
    }

    public function testPSR3LoggerWithoutCredentials(): void
    {
        ob_start();
        $logger = new PSR3Logger();
        $output = ob_get_clean();

        $this->assertNotNull($logger);
        $this->assertStringContainsString('No credentials provided', $output);
    }

    public function testPSR3LoggerLoggingInConsoleOnlyMode(): void
    {
        ob_start();
        $logger = new PSR3Logger();
        ob_get_clean();

        ob_start();
        $logger->info('PSR3 test message');
        $output = ob_get_clean();

        $this->assertStringContainsString('PSR3 test message', $output);
    }
}

