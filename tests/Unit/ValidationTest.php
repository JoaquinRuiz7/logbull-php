<?php

declare(strict_types=1);

namespace LogBull\Tests\Unit;

use LogBull\Internal\Validation;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    public function testValidateProjectIdWithValidUUID(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateProjectId('12345678-1234-1234-1234-123456789012');
    }

    public function testValidateProjectIdThrowsOnEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project ID cannot be empty');
        
        Validation::validateProjectId('');
    }

    public function testValidateProjectIdThrowsOnInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid project ID format');
        
        Validation::validateProjectId('invalid-uuid');
    }

    public function testValidateProjectIdTrimsWhitespace(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateProjectId('  12345678-1234-1234-1234-123456789012  ');
    }

    public function testValidateHostUrlWithValidHttp(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateHostUrl('http://localhost:4005');
    }

    public function testValidateHostUrlWithValidHttps(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateHostUrl('https://example.com');
    }

    public function testValidateHostUrlThrowsOnEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host URL cannot be empty');
        
        Validation::validateHostUrl('');
    }

    public function testValidateHostUrlThrowsOnInvalidScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host URL must use http or https scheme');
        
        Validation::validateHostUrl('ftp://example.com');
    }

    public function testValidateHostUrlThrowsOnMissingHost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid host URL format|Host URL must have a host component/');
        
        Validation::validateHostUrl('http://');
    }

    public function testValidateApiKeyWithValidKey(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateApiKey('validkey123');
    }

    public function testValidateApiKeyWithNull(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateApiKey(null);
    }

    public function testValidateApiKeyThrowsOnTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key must be at least 10 characters long');
        
        Validation::validateApiKey('short');
    }

    public function testValidateApiKeyThrowsOnInvalidCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid API key format');
        
        Validation::validateApiKey('invalid key!@#');
    }

    public function testValidateApiKeyAllowsValidCharacters(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateApiKey('valid_key-123.test');
    }

    public function testValidateLogMessageWithValidMessage(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateLogMessage('This is a valid log message');
    }

    public function testValidateLogMessageThrowsOnEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Log message cannot be empty');
        
        Validation::validateLogMessage('');
    }

    public function testValidateLogMessageThrowsOnWhitespaceOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Log message cannot be empty');
        
        Validation::validateLogMessage('   ');
    }

    public function testValidateLogMessageThrowsOnTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Log message too long');
        
        Validation::validateLogMessage(str_repeat('a', 10001));
    }

    public function testValidateLogMessageAllowsMaxLength(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateLogMessage(str_repeat('a', 10000));
    }

    public function testValidateLogFieldsWithValidFields(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateLogFields([
            'user_id' => '12345',
            'action' => 'login',
        ]);
    }

    public function testValidateLogFieldsWithNull(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateLogFields(null);
    }

    public function testValidateLogFieldsWithEmptyArray(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateLogFields([]);
    }

    public function testValidateLogFieldsThrowsOnTooManyFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many fields');
        
        $fields = [];
        for ($i = 0; $i < 101; $i++) {
            $fields["field_$i"] = $i;
        }
        
        Validation::validateLogFields($fields);
    }

    public function testValidateLogFieldsThrowsOnEmptyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Field key cannot be empty');
        
        Validation::validateLogFields(['' => 'value']);
    }

    public function testValidateLogFieldsThrowsOnKeyTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Field key too long');
        
        Validation::validateLogFields([str_repeat('a', 101) => 'value']);
    }

    public function testValidateLogFieldsAllowsMaxKeyLength(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validation::validateLogFields([str_repeat('a', 100) => 'value']);
    }

    public function testValidateLogFieldsAllowsMaxFieldCount(): void
    {
        $this->expectNotToPerformAssertions();
        
        $fields = [];
        for ($i = 0; $i < 100; $i++) {
            $fields["field_$i"] = $i;
        }
        
        Validation::validateLogFields($fields);
    }
}

