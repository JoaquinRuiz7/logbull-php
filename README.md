# LogBull PHP

<div align="center">

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![PHP Version](https://img.shields.io/badge/php-%5E8.0-blue.svg)](https://www.php.net/)

A PHP library for sending logs to [LogBull](https://github.com/logbull/logbull) - a simple log collection system.

</div>

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Usage Examples](#usage-examples)
  - [1. Standalone LogBullLogger](#1-standalone-logbulllogger)
  - [2. Monolog Integration](#2-monolog-integration)
  - [3. PSR-3 Logger](#3-psr-3-logger)
- [Configuration Options](#configuration-options)
- [API Reference](#api-reference)
- [Timestamp Precision](#timestamp-precision)
- [Requirements](#requirements)
- [License](#license)

## Features

- **Multiple integration options**: Standalone logger, Monolog handler, and PSR-3 wrapper
- **Context support**: Attach persistent context to logs (session_id, user_id, etc.)
- **Asynchronous sending**: Non-blocking HTTP requests using curl_multi
- **Zero dependencies**: No production dependencies required
- **PHP 8.0+**: Modern PHP with typed properties and named arguments

## Installation

Install via Composer:

```bash
composer require logbull/logbull
```

## Quick Start

The fastest way to start using LogBull is with the standalone logger:

```php
<?php

use LogBull\Core\LogBullLogger;

$logger = new LogBullLogger(
    projectId: 'LOGBULL_PROJECT_ID',
    host: 'http://LOGBULL_HOST',
    apiKey: 'YOUR_API_KEY' // optional
);

$logger->info('User logged in successfully', [
    'user_id' => '12345',
    'username' => 'john_doe',
    'ip' => '192.168.1.100'
]);

// Ensure all logs are sent before exiting
$logger->flush();
sleep(2);
```

## Usage Examples

### 1. Standalone LogBullLogger

```php
<?php

use LogBull\Core\LogBullLogger;
use LogBull\Core\Types;

// Initialize logger
$logger = new LogBullLogger(
    projectId: 'LOGBULL_PROJECT_ID',
    host: 'http://LOGBULL_HOST',
    apiKey: 'YOUR_API_KEY', // optional
    logLevel: Types::INFO
);

// Basic logging
$logger->info('Application started');

$logger->info('User logged in successfully', [
    'user_id' => '12345',
    'username' => 'john_doe',
    'ip' => '192.168.1.100'
]);

$logger->error('Database connection failed', [
    'database' => 'users_db',
    'error_code' => 500
]);

// Context management
$sessionLogger = $logger->withContext([
    'session_id' => 'sess_abc123',
    'user_id' => 'user_456'
]);

$sessionLogger->info('Processing user request', [
    'action' => 'purchase',
    'amount' => 99.99
]);

// Ensure all logs are sent before exiting
$logger->flush();
sleep(2);
```

#### Context Management

```php
<?php

// Attach persistent context to all subsequent logs
$sessionLogger = $logger->withContext([
    'session_id' => 'sess_abc123',
    'user_id' => 'user_456',
    'request_id' => 'req_789'
]);

// All logs from sessionLogger include the context automatically
$sessionLogger->info('User started checkout process', [
    'cart_items' => 3,
    'total_amount' => 149.99
]);
// Output includes: session_id, user_id, request_id + cart_items, total_amount

$sessionLogger->error('Payment processing failed', [
    'payment_method' => 'credit_card',
    'error_code' => 'DECLINED'
]);

// Context can be chained
$transactionLogger = $sessionLogger->withContext([
    'transaction_id' => 'txn_xyz789',
    'merchant_id' => 'merchant_123'
]);

$transactionLogger->info('Transaction completed', [
    'amount' => 149.99,
    'currency' => 'USD'
]);
// Includes all previous context + new transaction context
```

### 2. Monolog Integration

```php
<?php

use Monolog\Logger;
use Monolog\Level;
use LogBull\Handlers\MonologHandler;

// Create Monolog logger with LogBull handler
$handler = new MonologHandler(
    projectId: 'LOGBULL_PROJECT_ID',
    host: 'http://LOGBULL_HOST',
    apiKey: 'YOUR_API_KEY', // optional
    level: Level::Info
);

$logger = new Logger('app');
$logger->pushHandler($handler);

// Use standard Monolog logging
$logger->info('User action', [
    'user_id' => '12345',
    'action' => 'login',
    'ip' => '192.168.1.100'
]);

$logger->error('Payment failed', [
    'order_id' => 'ord_123',
    'amount' => 99.99,
    'currency' => 'USD'
]);

// Ensure all logs are sent before exiting
$handler->flush();
sleep(2);
```

### 3. PSR-3 Logger

```php
<?php

use LogBull\Handlers\PSR3Logger;
use LogBull\Core\Types;

// Create PSR-3 compatible logger
$logger = new PSR3Logger(
    projectId: 'LOGBULL_PROJECT_ID',
    host: 'http://LOGBULL_HOST',
    apiKey: 'YOUR_API_KEY', // optional
    logLevel: Types::INFO
);

// Use standard PSR-3 methods
$logger->info('API request', [
    'method' => 'POST',
    'path' => '/api/users',
    'status_code' => 201,
    'response_time_ms' => 45
]);

$logger->error('Database error', [
    'query' => 'SELECT * FROM users',
    'error' => 'Connection timeout'
]);

// Ensure all logs are sent before exiting
$logger->flush();
sleep(2);
```

## Configuration Options

### LogBullLogger Parameters

- `projectId` (required): Your LogBull project ID (UUID format)
- `host` (required): LogBull server URL (e.g., `http://localhost:4005`)
- `apiKey` (optional): API key for authentication
- `logLevel` (optional): Minimum log level to process (default: `INFO`)
- `context` (optional): Default context to attach to all logs

### Available Log Levels

- `DEBUG`: Detailed information for debugging
- `INFO`: General informational messages
- `WARNING`: Warning messages
- `ERROR`: Error messages
- `CRITICAL`: Critical error messages

## API Reference

### LogBullLogger Methods

- `debug(string $message, ?array $fields = null): void` - Log debug message
- `info(string $message, ?array $fields = null): void` - Log info message
- `warning(string $message, ?array $fields = null): void` - Log warning message
- `error(string $message, ?array $fields = null): void` - Log error message
- `critical(string $message, ?array $fields = null): void` - Log critical message
- `withContext(array $context): LogBullLogger` - Create new logger with additional context
- `flush(): void` - Immediately send all queued logs
- `shutdown(): void` - Stop background processing and send remaining logs

### MonologHandler Methods

- `flush(): void` - Immediately send all queued logs
- `close(): void` - Close the handler and send remaining logs

### PSR3Logger Methods

- All standard PSR-3 methods: `emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`, `log()`
- `flush(): void` - Immediately send all queued logs
- `shutdown(): void` - Stop and send remaining logs

## Timestamp Precision

**Important Note:** PHP uses **microsecond precision** (6 decimal places) for timestamps rather than nanosecond precision (9 decimal places) due to `DateTime` limitations.

Timestamps are in RFC3339 format with microseconds:

- Format: `2025-01-15T10:30:45.123456Z`
- The fractional seconds have **6 digits** (microseconds) instead of 9 digits (nanoseconds)
- All timestamps are in UTC timezone (indicated by `Z` suffix)

The library ensures that timestamps are monotonically increasing and unique, even when multiple logs are created within the same microsecond.

## Requirements

- PHP 8.0 or higher
- ext-curl (for HTTP requests)
- ext-json (for JSON encoding)

### Optional Dependencies

- `monolog/monolog` ^3.0 (for Monolog integration)
- `psr/log` ^3.0 (for PSR-3 integration)

## License

Apache 2.0 License

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## LogBull Server

This library requires a LogBull server instance. Visit [LogBull on GitHub](https://github.com/logbull/logbull) for server setup instructions.
