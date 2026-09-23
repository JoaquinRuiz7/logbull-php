<?php

declare(strict_types=1);

namespace LogBull\Tests\Unit;

use LogBull\Core\Sender;
use PHPUnit\Framework\TestCase;

class SenderTest extends TestCase
{
    private const PROJECT_ID = '12345678-1234-1234-1234-123456789012';
    // Closed port so requests fail fast without a server
    private const HOST = 'http://127.0.0.1:1';

    public function testDefaultBatchSizeKeepsLogsQueued(): void
    {
        $sender = new Sender(self::PROJECT_ID, self::HOST);

        for ($i = 0; $i < 999; $i++) {
            $sender->addLog($this->entry($i));
        }

        $this->assertSame(999, $this->queuedCount($sender));

        $sender->addLog($this->entry(999));

        $this->assertSame(0, $this->queuedCount($sender));
    }

    public function testCustomBatchSizeSendsWhenReached(): void
    {
        $sender = new Sender(self::PROJECT_ID, self::HOST, null, 3);

        $sender->addLog($this->entry(1));
        $sender->addLog($this->entry(2));
        $this->assertSame(2, $this->queuedCount($sender));

        $sender->addLog($this->entry(3));
        $this->assertSame(0, $this->queuedCount($sender));
    }

    public function testFlushIntervalSendsBeforeBatchIsFull(): void
    {
        $sender = new Sender(self::PROJECT_ID, self::HOST, null, 1_000, 0.05);

        $sender->addLog($this->entry(1));
        $this->assertSame(1, $this->queuedCount($sender));

        usleep(60_000);

        $sender->addLog($this->entry(2));
        $this->assertSame(0, $this->queuedCount($sender));
    }

    public function testNoFlushIntervalByDefault(): void
    {
        $sender = new Sender(self::PROJECT_ID, self::HOST);

        $sender->addLog($this->entry(1));
        usleep(60_000);
        $sender->addLog($this->entry(2));

        $this->assertSame(2, $this->queuedCount($sender));
    }

    public function testFlushSendsAllBatches(): void
    {
        $sender = new Sender(self::PROJECT_ID, self::HOST, null, 1_000);

        for ($i = 0; $i < 10; $i++) {
            $sender->addLog($this->entry($i));
        }

        $this->setBatchSize($sender, 3);
        $sender->flush();

        $this->assertSame(0, $this->queuedCount($sender));
    }

    public function testInvalidBatchSizeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch size must be at least 1');

        new Sender(self::PROJECT_ID, self::HOST, null, 0);
    }

    public function testInvalidFlushIntervalThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Flush interval must be greater than 0');

        new Sender(self::PROJECT_ID, self::HOST, null, 1_000, 0.0);
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(int $i): array
    {
        return [
            'level' => 'INFO',
            'message' => "Test message $i",
            'timestamp' => '2026-01-01T00:00:00.000000Z',
            'fields' => [],
        ];
    }

    private function queuedCount(Sender $sender): int
    {
        $property = new \ReflectionProperty(Sender::class, 'logQueue');

        return count($property->getValue($sender));
    }

    private function setBatchSize(Sender $sender, int $batchSize): void
    {
        $property = new \ReflectionProperty(Sender::class, 'batchSize');
        $property->setValue($sender, $batchSize);
    }
}
