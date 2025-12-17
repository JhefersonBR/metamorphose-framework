# Contexts

Metamorphose Framework uses explicit context objects to manage multi-tenant data and request information. This approach avoids global state and makes the code more testable and maintainable.

## Overview

The framework provides three context types:

1. **RequestContext**: Request-specific information
2. **TenantContext**: Tenant-specific information
3. **UnitContext**: Unit-specific information

All contexts are:
- Explicitly injected via dependency injection
- Populated via middleware
- Never rely on global mutable state
- Safe for persistent runtimes (Swoole, FrankenPHP)

## RequestContext

The `RequestContext` contains information about the current HTTP request.

### Features

- **Automatic request ID generation**: Each request gets a unique ID
- **User ID tracking**: Stores authenticated user ID (when available)
- **Request metadata**: Method, URI, and other request data

### Usage

```php
use Metamorphose\Kernel\Context\RequestContext;

class MyController
{
    private RequestContext $requestContext;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    public function index(...): ResponseInterface
    {
        $requestId = $this->requestContext->getRequestId();
        $userId = $this->requestContext->getUserId();
        
        // Use request ID for logging, tracing, etc.
    }
}
```

### Methods

- `getRequestId(): string` - Get unique request ID
- `getUserId(): ?string` - Get current user ID (if authenticated)
- `setUserId(?string $userId): void` - Set user ID
- `getRequestData(): array` - Get request metadata
- `setRequestData(array $data): void` - Set request metadata
- `clear(): void` - Reset context (generates new request ID)

### Request ID Format

Request IDs are generated using `random_bytes()` and converted to hexadecimal:
- Length: 32 characters (16 bytes)
- Format: Hexadecimal string
- Example: `a1b2c3d4e5f6789012345678901234ab`

## TenantContext

The `TenantContext` contains information about the current tenant.

### Population

The context is populated via middleware from:
1. `X-Tenant-ID` HTTP header (preferred)
2. `tenant_id` query parameter (fallback)
3. `X-Tenant-Code` HTTP header (optional)

### Usage

```php
use Metamorphose\Kernel\Context\TenantContext;

class MyController
{
    private TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function index(...): ResponseInterface
    {
        if (!$this->tenantContext->hasTenant()) {
            // Handle non-tenant request
            return $response->withStatus(400);
        }
        
        $tenantId = $this->tenantContext->getTenantId();
        $tenantCode = $this->tenantContext->getTenantCode();
        
        // Use tenant-specific data
    }
}
```

### Methods

- `getTenantId(): ?string` - Get tenant ID
- `setTenantId(?string $tenantId): void` - Set tenant ID
- `getTenantCode(): ?string` - Get tenant code
- `setTenantCode(?string $tenantCode): void` - Set tenant code
- `getTenantData(): array` - Get additional tenant data
- `setTenantData(array $data): void` - Set additional tenant data
- `hasTenant(): bool` - Check if tenant is set
- `clear(): void` - Reset context

### Example Request

```bash
curl -H "X-Tenant-ID: tenant-123" \
     -H "X-Tenant-Code: acme-corp" \
     http://localhost/api/products
```

## UnitContext

The `UnitContext` contains information about the current unit (sub-tenant).

### Population

The context is populated via middleware from:
1. `X-Unit-ID` HTTP header (preferred)
2. `unit_id` query parameter (fallback)
3. `X-Unit-Code` HTTP header (optional)

### Usage

```php
use Metamorphose\Kernel\Context\UnitContext;

class MyController
{
    private UnitContext $unitContext;

    public function __construct(UnitContext $unitContext)
    {
        $this->unitContext = $unitContext;
    }

    public function index(...): ResponseInterface
    {
        if (!$this->unitContext->hasUnit()) {
            // Handle non-unit request
            return $response->withStatus(400);
        }
        
        $unitId = $this->unitContext->getUnitId();
        $unitCode = $this->unitContext->getUnitCode();
        
        // Use unit-specific data
    }
}
```

### Methods

- `getUnitId(): ?string` - Get unit ID
- `setUnitId(?string $unitId): void` - Set unit ID
- `getUnitCode(): ?string` - Get unit code
- `setUnitCode(?string $unitCode): void` - Set unit code
- `getUnitData(): array` - Get additional unit data
- `setUnitData(array $data): void` - Set additional unit data
- `hasUnit(): bool` - Check if unit is set
- `clear(): void` - Reset context

### Example Request

```bash
curl -H "X-Tenant-ID: tenant-123" \
     -H "X-Unit-ID: unit-456" \
     -H "X-Unit-Code: warehouse-1" \
     http://localhost/api/inventory
```

## Using Multiple Contexts

You can use all contexts together:

```php
class MyController
{
    private RequestContext $requestContext;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;

    public function __construct(
        RequestContext $requestContext,
        TenantContext $tenantContext,
        UnitContext $unitContext
    ) {
        $this->requestContext = $requestContext;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
    }

    public function index(...): ResponseInterface
    {
        $data = [
            'request_id' => $this->requestContext->getRequestId(),
            'tenant_id' => $this->tenantContext->getTenantId(),
            'unit_id' => $this->unitContext->getUnitId(),
        ];
        
        // Use contexts to determine data scope
    }
}
```

## Context Middleware

Contexts are populated automatically by `ContextMiddleware` (registered in `app/Bootstrap/middleware.php`).

The middleware:
1. Extracts tenant/unit IDs from headers or query parameters
2. Populates context objects
3. Makes contexts available to all handlers

You don't need to manually populate contexts in your code.

## Contexts in Logging

Contexts are automatically included in log entries via `LogContext`:

```php
// Log entry automatically includes:
{
    "request_id": "a1b2c3d4...",
    "tenant_id": "tenant-123",
    "unit_id": "unit-456",
    "user_id": "user-789",
    "message": "Product created",
    "level": "info"
}
```

## Contexts in Database

Contexts are used by `ConnectionResolver` to determine which database connection to use:

```php
// Resolves tenant-specific connection
$connection = $connectionResolver->resolveTenant(
    $tenantContext->getTenantId()
);

// Resolves unit-specific connection
$connection = $connectionResolver->resolveUnit(
    $unitContext->getUnitId()
);
```

## Testing with Contexts

In tests, you can manually set context values:

```php
$tenantContext = new TenantContext();
$tenantContext->setTenantId('test-tenant-123');

$unitContext = new UnitContext();
$unitContext->setUnitId('test-unit-456');

$requestContext = new RequestContext();
$requestContext->setUserId('test-user-789');
```

## Best Practices

1. **Always inject contexts**: Never access contexts via global state
2. **Check context availability**: Use `hasTenant()` and `hasUnit()` before using IDs
3. **Use contexts explicitly**: Don't pass IDs around, pass context objects
4. **Respect context scope**: Use tenant/unit contexts only when appropriate
5. **Clear contexts in tests**: Reset contexts between test cases

## Common Patterns

### Pattern 1: Tenant-Required Endpoint

```php
public function index(...): ResponseInterface
{
    if (!$this->tenantContext->hasTenant()) {
        return $response->withStatus(400)
            ->withJson(['error' => 'Tenant ID required']);
    }
    
    // Process tenant-specific request
}
```

### Pattern 2: Optional Unit Context

```php
public function index(...): ResponseInterface
{
    $scope = $this->unitContext->hasUnit() 
        ? 'unit' 
        : 'tenant';
    
    // Use appropriate scope
}
```

### Pattern 3: Context in Logging

```php
$this->logger->info('Action performed', [
    'tenant_id' => $this->tenantContext->getTenantId(),
    'unit_id' => $this->unitContext->getUnitId(),
    'user_id' => $this->requestContext->getUserId(),
]);
```

