# Logging

Metamorphose Framework includes a comprehensive logging system with automatic HTTP request logging and context enrichment.

## Configuration

Logging is configured in `config/log.php`:

```php
<?php

return [
    'enabled' => filter_var(getenv('LOG_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
    'channel' => getenv('LOG_CHANNEL') ?: 'metamorphose',
    'level' => getenv('LOG_LEVEL') ?: 'info',
    'path' => getenv('LOG_PATH') ?: __DIR__ . '/../storage/logs',
    'http_log' => filter_var(getenv('HTTP_LOG_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
];
```

### Options

- `enabled`: Enable/disable logging (uses NullLogger when disabled)
- `channel`: Log channel name
- `level`: Minimum log level (debug, info, notice, warning, error, critical, alert, emergency)
- `path`: Directory for log files
- `http_log`: Enable/disable automatic HTTP request logging

## Using the Logger

### In Controllers

```php
use Metamorphose\Kernel\Log\LoggerInterface;

class MyController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function index(...): ResponseInterface
    {
        $this->logger->info('Products listed', [
            'count' => 10,
            'filters' => ['category' => 'electronics'],
        ]);
        
        // ...
    }
}
```

### In Services

```php
use Metamorphose\Kernel\Log\LoggerInterface;

class ProductService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(array $data): void
    {
        try {
            // Create product
            $this->logger->info('Product created', ['product_id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
}
```

## Log Levels

The framework supports all PSR-3 log levels:

```php
$logger->debug('Detailed debugging information');
$logger->info('Informational messages');
$logger->notice('Normal but significant events');
$logger->warning('Warning messages');
$logger->error('Error messages');
$logger->critical('Critical conditions');
$logger->alert('Action must be taken immediately');
$logger->emergency('System is unusable');
```

## Context Enrichment

Log entries are automatically enriched with context information via `LogContext`:

```php
// Your log entry
$logger->info('Product created', ['product_id' => 123]);

// Actual log entry includes:
{
    "request_id": "a1b2c3d4e5f6...",
    "tenant_id": "tenant-123",
    "unit_id": "unit-456",
    "user_id": "user-789",
    "message": "Product created",
    "product_id": 123,
    "level": "info",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Context Fields

- `request_id`: Unique request identifier
- `tenant_id`: Current tenant ID (if available)
- `unit_id`: Current unit ID (if available)
- `user_id`: Current user ID (if available)

## HTTP Request Logging

The `HttpLogMiddleware` automatically logs all HTTP requests:

### What Gets Logged

- HTTP method (GET, POST, PUT, DELETE, etc.)
- Request URI
- Response status code
- Response time (in milliseconds)
- Client IP address
- Context information (request_id, tenant_id, unit_id, user_id)

### Log Level Based on Status Code

- `info`: Status codes 200-399
- `warning`: Status codes 400-499
- `error`: Status codes 500+

### Example Log Entry

```json
{
    "request_id": "a1b2c3d4e5f6...",
    "tenant_id": "tenant-123",
    "unit_id": null,
    "user_id": "user-789",
    "method": "POST",
    "uri": "/api/products",
    "status_code": 201,
    "duration_ms": 45.23,
    "ip": "192.168.1.100",
    "level": "info",
    "message": "HTTP Request",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Disabling HTTP Logging

Set `http_log` to `false` in `config/log.php`:

```php
'http_log' => false,
```

Or via environment variable:

```bash
HTTP_LOG_ENABLED=false
```

## Log Files

Logs are written to files in the configured path:

```
storage/logs/
├── 2024-01-15.log
├── 2024-01-16.log
└── 2024-01-17.log
```

Files are named by date (YYYY-MM-DD.log).

## Disabling Logging

When logging is disabled, the framework uses `NullLogger`, which discards all log messages:

```php
'enabled' => false,
```

This is useful for:
- Development environments where you don't need logs
- Performance testing
- Reducing I/O operations

## Custom Log Handlers

The framework uses Monolog. You can extend logging by registering custom handlers in `LoggerFactory`:

```php
// In app/Kernel/Log/LoggerFactory.php
$logger->pushHandler(new CustomHandler());
```

## Best Practices

1. **Use appropriate log levels**: Don't log everything as info
2. **Include context**: Add relevant data to log entries
3. **Don't log sensitive data**: Avoid logging passwords, tokens, etc.
4. **Use structured logging**: Pass arrays for better parsing
5. **Log errors with context**: Include enough information to debug
6. **Monitor log size**: Rotate logs regularly
7. **Use HTTP logging**: Enable HTTP logging for API monitoring

## Common Patterns

### Pattern 1: Logging with Context

```php
$this->logger->info('Action performed', [
    'action' => 'product_created',
    'product_id' => $id,
    'tenant_id' => $this->tenantContext->getTenantId(),
]);
```

### Pattern 2: Error Logging

```php
try {
    // Operation
} catch (\Exception $e) {
    $this->logger->error('Operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'context' => $contextData,
    ]);
    throw $e;
}
```

### Pattern 3: Debug Logging

```php
if ($this->config['debug']) {
    $this->logger->debug('Debug information', [
        'variable' => $value,
        'state' => $state,
    ]);
}
```

## Log Rotation

For production, consider using log rotation tools:

- **logrotate** (Linux): Rotate logs based on size or time
- **Monolog RotatingFileHandler**: Built-in rotation support

Example with Monolog:

```php
use Monolog\Handler\RotatingFileHandler;

$handler = new RotatingFileHandler(
    $logPath . '/app.log',
    30, // Keep 30 days
    Logger::INFO
);
```

