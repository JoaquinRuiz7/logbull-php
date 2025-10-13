<?php

declare(strict_types=1);

namespace LogBull\Tests\Unit;

use LogBull\Core\Timestamp;
use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
{
    protected function setUp(): void
    {
        Timestamp::reset();
    }

    public function testGeneratesRFC3339Format(): void
    {
        $timestamp = Timestamp::generateUniqueTimestamp();

        // Should match RFC3339 format with microseconds: 2025-01-15T10:30:45.123456Z
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $timestamp
        );

        // Should be parseable by DateTime
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $timestamp);
        $this->assertInstanceOf(\DateTime::class, $dateTime);
    }

    public function testGeneratesUniqueTimestamps(): void
    {
        $timestamps = [];
        
        for ($i = 0; $i < 100; $i++) {
            $timestamps[] = Timestamp::generateUniqueTimestamp();
        }

        // All timestamps should be unique
        $unique = array_unique($timestamps);
        $this->assertCount(100, $unique);
    }

    public function testGeneratesMonotonicallyIncreasingTimestamps(): void
    {
        $timestamps = [];
        
        for ($i = 0; $i < 100; $i++) {
            $timestamps[] = Timestamp::generateUniqueTimestamp();
        }

        // Each timestamp should be greater than the previous
        for ($i = 1; $i < count($timestamps); $i++) {
            $this->assertGreaterThan($timestamps[$i - 1], $timestamps[$i]);
        }
    }

    public function testHandlesDuplicatesInSameMicrosecond(): void
    {
        // This test verifies that even if called multiple times in the same microsecond,
        // we get unique, increasing timestamps
        $timestamps = [];
        
        // Generate many timestamps as fast as possible
        for ($i = 0; $i < 1000; $i++) {
            $timestamps[] = Timestamp::generateUniqueTimestamp();
        }

        // All should be unique
        $unique = array_unique($timestamps);
        $this->assertCount(1000, $unique);

        // All should be monotonically increasing
        for ($i = 1; $i < count($timestamps); $i++) {
            $this->assertGreaterThan($timestamps[$i - 1], $timestamps[$i]);
        }
    }

    public function testResetClearsState(): void
    {
        $ts1 = Timestamp::generateUniqueTimestamp();
        
        Timestamp::reset();
        
        // After reset, the next timestamp might be earlier than ts1
        // (depending on system time), but it should still be valid
        $ts2 = Timestamp::generateUniqueTimestamp();
        
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $ts2
        );
    }

    public function testMicrosecondPrecision(): void
    {
        $timestamp = Timestamp::generateUniqueTimestamp();
        
        // Extract the fractional seconds part
        preg_match('/\.(\d+)Z$/', $timestamp, $matches);
        
        $this->assertNotEmpty($matches);
        $this->assertEquals(6, strlen($matches[1]), 'Should have exactly 6 digits for microseconds');
    }

    public function testUTCTimezone(): void
    {
        $timestamp = Timestamp::generateUniqueTimestamp();
        
        // Should end with 'Z' indicating UTC
        $this->assertStringEndsWith('Z', $timestamp);
        
        // Create DateTime and verify it's in UTC
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $timestamp);
        // Timezone name can be either 'UTC' or 'Z' depending on PHP version
        $tzName = $dateTime->getTimezone()->getName();
        $this->assertTrue($tzName === 'UTC' || $tzName === 'Z', "Timezone should be UTC or Z, got: $tzName");
    }
}

