<?php

declare(strict_types=1);

namespace LogBull\Tests\Unit;

use LogBull\Core\Types;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testGetPriorityReturnsCorrectValues(): void
    {
        $this->assertEquals(10, Types::getPriority(Types::DEBUG));
        $this->assertEquals(20, Types::getPriority(Types::INFO));
        $this->assertEquals(30, Types::getPriority(Types::WARNING));
        $this->assertEquals(40, Types::getPriority(Types::ERROR));
        $this->assertEquals(50, Types::getPriority(Types::CRITICAL));
    }

    public function testGetPriorityReturnsZeroForInvalidLevel(): void
    {
        $this->assertEquals(0, Types::getPriority('INVALID'));
    }

    public function testIsValidLevelReturnsTrueForValidLevels(): void
    {
        $this->assertTrue(Types::isValidLevel(Types::DEBUG));
        $this->assertTrue(Types::isValidLevel(Types::INFO));
        $this->assertTrue(Types::isValidLevel(Types::WARNING));
        $this->assertTrue(Types::isValidLevel(Types::ERROR));
        $this->assertTrue(Types::isValidLevel(Types::CRITICAL));
    }

    public function testIsValidLevelReturnsFalseForInvalidLevel(): void
    {
        $this->assertFalse(Types::isValidLevel('INVALID'));
        $this->assertFalse(Types::isValidLevel(''));
        $this->assertFalse(Types::isValidLevel('debug')); // lowercase
    }

    public function testGetValidLevelsReturnsAllLevels(): void
    {
        $levels = Types::getValidLevels();
        
        $this->assertIsArray($levels);
        $this->assertCount(5, $levels);
        $this->assertContains(Types::DEBUG, $levels);
        $this->assertContains(Types::INFO, $levels);
        $this->assertContains(Types::WARNING, $levels);
        $this->assertContains(Types::ERROR, $levels);
        $this->assertContains(Types::CRITICAL, $levels);
    }

    public function testNormalizeLevelHandlesWarnVariant(): void
    {
        $this->assertEquals(Types::WARNING, Types::normalizeLevel('WARN'));
        $this->assertEquals(Types::WARNING, Types::normalizeLevel('warn'));
        $this->assertEquals(Types::WARNING, Types::normalizeLevel('Warn'));
    }

    public function testNormalizeLevelHandlesFatalVariant(): void
    {
        $this->assertEquals(Types::CRITICAL, Types::normalizeLevel('FATAL'));
        $this->assertEquals(Types::CRITICAL, Types::normalizeLevel('fatal'));
        $this->assertEquals(Types::CRITICAL, Types::normalizeLevel('Fatal'));
    }

    public function testNormalizeLevelConvertsToUppercase(): void
    {
        $this->assertEquals(Types::DEBUG, Types::normalizeLevel('debug'));
        $this->assertEquals(Types::INFO, Types::normalizeLevel('info'));
        $this->assertEquals(Types::WARNING, Types::normalizeLevel('warning'));
        $this->assertEquals(Types::ERROR, Types::normalizeLevel('error'));
        $this->assertEquals(Types::CRITICAL, Types::normalizeLevel('critical'));
    }

    public function testNormalizeLevelReturnsUnchangedForUnknown(): void
    {
        $this->assertEquals('UNKNOWN', Types::normalizeLevel('unknown'));
        $this->assertEquals('CUSTOM', Types::normalizeLevel('custom'));
    }

    public function testPriorityOrdering(): void
    {
        $this->assertLessThan(Types::getPriority(Types::WARNING), Types::getPriority(Types::INFO));
        $this->assertLessThan(Types::getPriority(Types::ERROR), Types::getPriority(Types::WARNING));
        $this->assertLessThan(Types::getPriority(Types::CRITICAL), Types::getPriority(Types::ERROR));
    }
}

