<?php

declare(strict_types=1);

namespace LogBull\Tests\Unit;

use LogBull\Internal\Formatting;
use PHPUnit\Framework\TestCase;

class FormattingTest extends TestCase
{
    public function testFormatMessageTrimsWhitespace(): void
    {
        $result = Formatting::formatMessage('  test message  ');
        
        $this->assertEquals('test message', $result);
    }

    public function testFormatMessageTruncatesLongMessages(): void
    {
        $longMessage = str_repeat('a', 15000);
        $result = Formatting::formatMessage($longMessage);
        
        $this->assertEquals(10000, strlen($result));
        $this->assertStringEndsWith('...', $result);
    }

    public function testFormatMessageKeepsMaxLength(): void
    {
        $message = str_repeat('a', 10000);
        $result = Formatting::formatMessage($message);
        
        $this->assertEquals(10000, strlen($result));
    }

    public function testFormatMessageHandlesEmptyString(): void
    {
        $result = Formatting::formatMessage('');
        
        $this->assertEquals('', $result);
    }

    public function testEnsureFieldsWithValidFields(): void
    {
        $fields = [
            'user_id' => '12345',
            'count' => 42,
            'active' => true,
        ];
        
        $result = Formatting::ensureFields($fields);
        
        $this->assertEquals($fields, $result);
    }

    public function testEnsureFieldsWithNull(): void
    {
        $result = Formatting::ensureFields(null);
        
        $this->assertEquals([], $result);
    }

    public function testEnsureFieldsWithEmptyArray(): void
    {
        $result = Formatting::ensureFields([]);
        
        $this->assertEquals([], $result);
    }

    public function testEnsureFieldsTrimsKeys(): void
    {
        $fields = [
            '  user_id  ' => '12345',
            'count' => 42,
        ];
        
        $result = Formatting::ensureFields($fields);
        
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayNotHasKey('  user_id  ', $result);
        $this->assertEquals('12345', $result['user_id']);
    }

    public function testEnsureFieldsRemovesEmptyKeys(): void
    {
        $fields = [
            '' => 'should be removed',
            'user_id' => '12345',
        ];
        
        $result = Formatting::ensureFields($fields);
        
        $this->assertArrayNotHasKey('', $result);
        $this->assertArrayHasKey('user_id', $result);
    }

    public function testEnsureFieldsRemovesWhitespaceOnlyKeys(): void
    {
        $fields = [
            '   ' => 'should be removed',
            'user_id' => '12345',
        ];
        
        $result = Formatting::ensureFields($fields);
        
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('user_id', $result);
    }

    public function testEnsureFieldsHandlesNonSerializableValues(): void
    {
        $resource = fopen('php://memory', 'r');
        
        $fields = [
            'resource' => $resource,
            'user_id' => '12345',
        ];
        
        $result = Formatting::ensureFields($fields);
        
        $this->assertArrayHasKey('resource', $result);
        $this->assertIsString($result['resource']);
        
        fclose($resource);
    }

    public function testEnsureFieldsHandlesComplexTypes(): void
    {
        $fields = [
            'string' => 'value',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => ['key' => 'value'],
        ];
        
        $result = Formatting::ensureFields($fields);
        
        $this->assertEquals($fields, $result);
    }

    public function testMergeFieldsMergesTwoArrays(): void
    {
        $base = [
            'user_id' => '12345',
            'role' => 'admin',
        ];
        
        $additional = [
            'action' => 'login',
        ];
        
        $result = Formatting::mergeFields($base, $additional);
        
        $this->assertEquals([
            'user_id' => '12345',
            'role' => 'admin',
            'action' => 'login',
        ], $result);
    }

    public function testMergeFieldsOverridesValues(): void
    {
        $base = [
            'user_id' => '12345',
            'count' => 10,
        ];
        
        $additional = [
            'count' => 20,
        ];
        
        $result = Formatting::mergeFields($base, $additional);
        
        $this->assertEquals([
            'user_id' => '12345',
            'count' => 20,
        ], $result);
    }

    public function testMergeFieldsWithEmptyBase(): void
    {
        $result = Formatting::mergeFields([], ['key' => 'value']);
        
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testMergeFieldsWithEmptyAdditional(): void
    {
        $result = Formatting::mergeFields(['key' => 'value'], []);
        
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testMergeFieldsSanitizesKeys(): void
    {
        $base = [
            '  user_id  ' => '12345',
        ];
        
        $additional = [
            '  action  ' => 'login',
        ];
        
        $result = Formatting::mergeFields($base, $additional);
        
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('action', $result);
        $this->assertArrayNotHasKey('  user_id  ', $result);
        $this->assertArrayNotHasKey('  action  ', $result);
    }
}

