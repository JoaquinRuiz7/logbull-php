<?php

declare(strict_types=1);

namespace LogBull\Core;

/**
 * Asynchronous batch sender for LogBull
 * Handles batching, queuing, and HTTP transmission of logs
 */
class Sender
{
    private const BATCH_SIZE = 1_000;
    private const QUEUE_CAPACITY = 10_000;
    private const HTTP_TIMEOUT = 30;

    private string $projectId;
    private string $host;
    private ?string $apiKey;
    
    /** @var array<array<string, mixed>> */
    private array $logQueue = [];
    
    /** @var resource|null */
    private $multiHandle = null;
    
    /** @var array<int, resource> */
    private array $activeHandles = [];
    
    /** @var array<int, array<array<string, mixed>>> */
    private array $handleLogs = [];
    
    private bool $shutdown = false;

    public function __construct(string $projectId, string $host, ?string $apiKey = null)
    {
        $this->projectId = trim($projectId);
        $this->host = rtrim(trim($host), '/');
        $this->apiKey = $apiKey !== null ? trim($apiKey) : null;
        
        $this->multiHandle = curl_multi_init();
        if ($this->multiHandle !== false) {
            curl_multi_setopt($this->multiHandle, CURLMOPT_PIPELINING, 1);
        }
    }

    /**
     * Add a log entry to the queue
     * 
     * @param array<string, mixed> $entry
     */
    public function addLog(array $entry): void
    {
        if ($this->shutdown) {
            return;
        }

        if (count($this->logQueue) >= self::QUEUE_CAPACITY) {
            fwrite(STDERR, "LogBull: log queue full, dropping log\n");
            return;
        }

        $this->logQueue[] = $entry;

        // Auto-send if batch size reached
        if (count($this->logQueue) >= self::BATCH_SIZE) {
            $this->sendBatch();
        }
    }

    /**
     * Force send current batch immediately
     */
    public function flush(): void
    {
        $this->sendBatch();
        $this->processActiveRequests();
    }

    /**
     * Stop processing and send remaining logs
     */
    public function shutdown(): void
    {
        if ($this->shutdown) {
            return;
        }

        $this->shutdown = true;

        // Send remaining logs
        $this->sendBatch();

        // Wait for in-flight requests to complete (with timeout)
        $startTime = time();
        $maxWaitTime = 5; // 5 seconds

        while (!empty($this->activeHandles) && (time() - $startTime) < $maxWaitTime) {
            $this->processActiveRequests();
            if (!empty($this->activeHandles)) {
                usleep(100_000); // 100ms
            }
        }

        if (!empty($this->activeHandles)) {
            fwrite(STDERR, "LogBull Sender: Shutdown completed with " . count($this->activeHandles) . " requests still in-flight\n");
        }

        // Cleanup
        if ($this->multiHandle !== null) {
            foreach ($this->activeHandles as $handle) {
                curl_multi_remove_handle($this->multiHandle, $handle);
                curl_close($handle);
            }
            curl_multi_close($this->multiHandle);
            $this->multiHandle = null;
        }
    }

    /**
     * Send a batch of logs (fire-and-forget async)
     */
    private function sendBatch(): void
    {
        if (empty($this->logQueue) || $this->multiHandle === null) {
            return;
        }

        // Take up to BATCH_SIZE logs from the queue
        $logsToSend = array_splice($this->logQueue, 0, self::BATCH_SIZE);

        // Create curl handle for async request
        $handle = $this->createCurlHandle($logsToSend);
        
        if ($handle === null) {
            return;
        }

        // Add to multi handle for async execution
        $handleId = (int)$handle;
        curl_multi_add_handle($this->multiHandle, $handle);
        $this->activeHandles[$handleId] = $handle;
        $this->handleLogs[$handleId] = $logsToSend;

        // Process without blocking
        $this->processActiveRequests(false);
    }

    /**
     * Create curl handle for HTTP request
     * 
     * @param array<array<string, mixed>> $logs
     * @return resource|null
     */
    private function createCurlHandle(array $logs)
    {
        $batch = ['logs' => $logs];
        $data = json_encode($batch, JSON_THROW_ON_ERROR);

        $url = "{$this->host}/api/v1/logs/receiving/{$this->projectId}";

        $handle = curl_init($url);
        if ($handle === false) {
            fwrite(STDERR, "LogBull: Failed to initialize curl handle\n");
            return null;
        }

        $headers = [
            'Content-Type: application/json',
            'User-Agent: LogBull-PHP-Client/1.0',
        ];

        if ($this->apiKey !== null) {
            $headers[] = "X-API-Key: {$this->apiKey}";
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        return $handle;
    }

    /**
     * Process active curl requests
     */
    private function processActiveRequests(bool $blocking = false): void
    {
        if ($this->multiHandle === null || empty($this->activeHandles)) {
            return;
        }

        do {
            $status = curl_multi_exec($this->multiHandle, $active);
            
            if ($status !== CURLM_OK) {
                break;
            }

            // Check for completed requests
            while ($info = curl_multi_info_read($this->multiHandle)) {
                if ($info['msg'] === CURLMSG_DONE) {
                    $this->handleCompletedRequest($info['handle'], $info['result']);
                }
            }

            if ($blocking && $active > 0) {
                curl_multi_select($this->multiHandle, 0.1);
            }
        } while ($blocking && $active > 0);
    }

    /**
     * Handle a completed curl request
     * 
     * @param resource $handle
     */
    private function handleCompletedRequest($handle, int $result): void
    {
        $handleId = (int)$handle;
        $sentLogs = $this->handleLogs[$handleId] ?? [];

        if ($result !== CURLE_OK) {
            $error = curl_error($handle);
            fwrite(STDERR, "LogBull Sender: HTTP request failed: $error\n");
        } else {
            $response = curl_multi_getcontent($handle);
            $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if ($statusCode !== 200 && $statusCode !== 202) {
                fwrite(STDERR, "LogBull Sender: Server returned error status $statusCode\n");
                if ($response !== null && $response !== false) {
                    fwrite(STDERR, "LogBull Sender: Error response body: $response\n");
                }
            } elseif ($response !== null && $response !== false) {
                $this->handleResponse($response, $sentLogs);
            }
        }

        // Cleanup
        curl_multi_remove_handle($this->multiHandle, $handle);
        curl_close($handle);
        unset($this->activeHandles[$handleId]);
        unset($this->handleLogs[$handleId]);
    }

    /**
     * Handle HTTP response and check for rejected logs
     * 
     * @param array<array<string, mixed>> $sentLogs
     */
    private function handleResponse(string $response, array $sentLogs): void
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            
            if (isset($data['rejected']) && $data['rejected'] > 0) {
                $this->handleRejectedLogs($data, $sentLogs);
            }
        } catch (\JsonException $e) {
            // Ignore parse errors
        }
    }

    /**
     * Handle rejected logs from server
     * 
     * @param array<string, mixed> $response
     * @param array<array<string, mixed>> $sentLogs
     */
    private function handleRejectedLogs(array $response, array $sentLogs): void
    {
        fwrite(STDERR, "LogBull: Rejected {$response['rejected']} log entries\n");

        if (isset($response['errors']) && is_array($response['errors']) && !empty($response['errors'])) {
            fwrite(STDERR, "LogBull: Rejected log details:\n");

            foreach ($response['errors'] as $error) {
                if (!is_array($error) || !isset($error['index'], $error['message'])) {
                    continue;
                }

                $index = $error['index'];
                $message = $error['message'];

                if ($index >= 0 && $index < count($sentLogs)) {
                    $log = $sentLogs[$index];
                    fwrite(STDERR, "  - Log #$index rejected ($message):\n");
                    fwrite(STDERR, "    Level: {$log['level']}\n");
                    fwrite(STDERR, "    Message: {$log['message']}\n");
                    fwrite(STDERR, "    Timestamp: {$log['timestamp']}\n");

                    if (isset($log['fields']) && !empty($log['fields'])) {
                        $fieldsJson = json_encode($log['fields']);
                        fwrite(STDERR, "    Fields: $fieldsJson\n");
                    }
                }
            }
        }
    }

    public function __destruct()
    {
        $this->shutdown();
    }
}

