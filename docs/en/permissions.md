# Permissions

Metamorphose Framework includes a multi-scope permission system that validates permissions based on the current context (global, tenant, or unit).

## Overview

The permission system supports three scopes:

- **Global**: System-wide permissions
- **Tenant**: Tenant-specific permissions
- **Unit**: Unit-specific permissions

## Permission Codes

Permission codes follow a naming convention:

- `global:permission_name` - Global scope permission
- `tenant:permission_name` - Tenant scope permission
- `unit:permission_name` - Unit scope permission

If no prefix is provided, the permission defaults to global scope.

## PermissionService

The `PermissionService` validates permissions based on the current context.

### Usage

```php
use Metamorphose\Kernel\Permission\PermissionService;

class MyController
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function create(...): ResponseInterface
    {
        if (!$this->permissionService->hasPermission('tenant:products.create')) {
            return $response->withStatus(403)
                ->withJson(['error' => 'Permission denied']);
        }
        
        // Create product
    }
}
```

### Methods

- `hasPermission(string $permissionCode, ?string $userId = null): bool` - Check if permission is granted

### Permission Resolution

The service automatically resolves permission scope:

```php
// Global permission
$hasPermission = $permissionService->hasPermission('global:users.manage');

// Tenant permission (uses TenantContext)
$hasPermission = $permissionService->hasPermission('tenant:products.create');

// Unit permission (uses UnitContext)
$hasPermission = $permissionService->hasPermission('unit:inventory.manage');
```

## PermissionResolver

The `PermissionResolver` determines the scope of a permission code and resolves context IDs.

### Usage

```php
use Metamorphose\Kernel\Permission\PermissionResolver;

$resolver = new PermissionResolver($tenantContext, $unitContext);

$scope = $resolver->resolveScope('tenant:products.create');
// Returns: 'tenant'

$contextId = $resolver->getContextId('tenant');
// Returns: tenant ID from TenantContext
```

## Implementation

The permission system is designed to be extended. By default, `PermissionService` returns `false` (deny by default) for security.

### Extending PermissionService

To implement actual permission checking, extend `PermissionService` or create a custom implementation:

```php
<?php

namespace Metamorphose\Modules\Auth\Service;

use Metamorphose\Kernel\Permission\PermissionService;
use Metamorphose\Kernel\Permission\PermissionResolver;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;

class CustomPermissionService extends PermissionService
{
    private PermissionRepository $repository;

    public function __construct(
        PermissionResolver $resolver,
        TenantContext $tenantContext,
        UnitContext $unitContext,
        PermissionRepository $repository
    ) {
        parent::__construct($resolver, $tenantContext, $unitContext);
        $this->repository = $repository;
    }

    protected function checkGlobalPermission(string $permissionCode, ?string $userId): bool
    {
        if ($userId === null) {
            return false;
        }
        
        return $this->repository->hasGlobalPermission($userId, $permissionCode);
    }

    protected function checkTenantPermission(
        string $permissionCode,
        string $tenantId,
        ?string $userId
    ): bool {
        if ($userId === null) {
            return false;
        }
        
        return $this->repository->hasTenantPermission($userId, $tenantId, $permissionCode);
    }

    protected function checkUnitPermission(
        string $permissionCode,
        string $unitId,
        ?string $userId
    ): bool {
        if ($userId === null) {
            return false;
        }
        
        return $this->repository->hasUnitPermission($userId, $unitId, $permissionCode);
    }
}
```

## Permission Middleware

You can create middleware to check permissions automatically:

```php
<?php

namespace Metamorphose\Modules\Auth\Middleware;

use Metamorphose\Kernel\Permission\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PermissionMiddleware implements MiddlewareInterface
{
    private PermissionService $permissionService;
    private string $permissionCode;

    public function __construct(
        PermissionService $permissionService,
        string $permissionCode
    ) {
        $this->permissionService = $permissionService;
        $this->permissionCode = $permissionCode;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $userId = $request->getAttribute('user_id');
        
        if (!$this->permissionService->hasPermission($this->permissionCode, $userId)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Permission denied']));
            return $response->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        }
        
        return $handler->handle($request);
    }
}
```

### Using Permission Middleware

```php
// In your module routes
$app->post('/products', ProductController::class . ':create')
    ->add(new PermissionMiddleware($permissionService, 'tenant:products.create'));
```

## Permission Naming Conventions

Use a consistent naming convention for permissions:

```
{scope}:{resource}.{action}
```

Examples:
- `global:users.create`
- `global:users.update`
- `global:users.delete`
- `tenant:products.create`
- `tenant:products.update`
- `tenant:orders.view`
- `unit:inventory.manage`
- `unit:reports.view`

## Best Practices

1. **Deny by default**: Return `false` when permission cannot be determined
2. **Check context**: Ensure tenant/unit context is available before checking permissions
3. **Use explicit codes**: Use clear, descriptive permission codes
4. **Cache permissions**: Cache permission checks for performance
5. **Log permission denials**: Log failed permission checks for security auditing
6. **Use middleware**: Apply permissions via middleware when possible
7. **Test permissions**: Write tests for permission logic

## Common Patterns

### Pattern 1: Controller Permission Check

```php
public function create(...): ResponseInterface
{
    $userId = $this->requestContext->getUserId();
    
    if (!$this->permissionService->hasPermission('tenant:products.create', $userId)) {
        return $response->withStatus(403)
            ->withJson(['error' => 'Permission denied']);
    }
    
    // Create product
}
```

### Pattern 2: Service Permission Check

```php
public function delete(int $id): void
{
    $userId = $this->requestContext->getUserId();
    
    if (!$this->permissionService->hasPermission('tenant:products.delete', $userId)) {
        throw new PermissionDeniedException('Cannot delete product');
    }
    
    // Delete product
}
```

### Pattern 3: Multiple Permission Check

```php
public function update(...): ResponseInterface
{
    $userId = $this->requestContext->getUserId();
    
    $canUpdate = $this->permissionService->hasPermission('tenant:products.update', $userId);
    $canPublish = $this->permissionService->hasPermission('tenant:products.publish', $userId);
    
    if (!$canUpdate) {
        return $response->withStatus(403)
            ->withJson(['error' => 'Cannot update product']);
    }
    
    // Update product
    if ($canPublish) {
        // Publish product
    }
}
```

## Security Considerations

1. **Always validate permissions**: Never skip permission checks
2. **Use context-aware checks**: Ensure permissions are checked in the correct scope
3. **Validate user identity**: Ensure user ID is authenticated before checking permissions
4. **Log security events**: Log permission denials and security-related actions
5. **Use HTTPS**: Always use HTTPS in production
6. **Rate limiting**: Implement rate limiting for sensitive operations

