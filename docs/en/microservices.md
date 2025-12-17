# Microservices and Remote Module Execution

Metamorphose Framework is designed to support both monolithic and microservices architectures. You can easily extract modules into separate microservices without changing the module code itself.

## Overview

The framework allows you to:
- Run all modules in a single monolithic application
- Extract specific modules to run as separate microservices
- Mix local and remote modules transparently
- Migrate modules gradually from monolith to microservices
- **Switch between local and remote by configuration only, without changing code**

## Architecture

### Monolithic Mode (Default)

All modules run in the same process:

```
┌─────────────────────────────────┐
│     Monolithic Application      │
│  ┌──────────┐  ┌──────────┐   │
│  │ Module A  │  │ Module B │   │
│  └──────────┘  └──────────┘   │
│  ┌──────────┐  ┌──────────┐   │
│  │ Module C  │  │ Module D │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

### Microservices Mode

Modules can run as separate services:

```
┌─────────────────────────────────┐
│     Main Application            │
│  ┌──────────┐  ┌──────────┐   │
│  │ Module A  │  │ Module B │   │
│  │ (Local)   │  │ (Local)   │   │
│  └──────────┘  └──────────┘   │
│         │              │        │
│         ▼              ▼        │
│  ┌──────────┐  ┌──────────┐   │
│  │ Permission│  │ Stock    │   │
│  │ Service   │  │ Service  │   │
│  │ (Remote)  │  │ (Remote) │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

## Module Execution System

The framework uses a **module executor system** that allows executing module actions locally or remotely in a transparent way.

### Components

#### ModuleExecutorInterface

Generic interface that defines the contract for module execution:

```php
interface ModuleExecutorInterface
{
    public function execute(string $moduleName, string $action, array $payload = []): mixed;
}
```

#### LocalModuleExecutor

Executes modules directly in the same process (monolithic):

- Resolves module via ModuleLoader
- Calls method directly
- Injects contexts if needed
- Executes in the same process

#### RemoteModuleExecutor

Executes modules configured as remote via HTTP:

- Sends HTTP request to microservice
- Preserves context (tenant, unit, request, user)
- Returns result as if it were local execution
- Handles network errors and invalid responses

#### ModuleRunner

Facade that automatically decides which executor to use:

- Reads module configuration
- Checks if module is `local` or `remote`
- Delegates to appropriate executor
- **No module needs to know if it's local or remote**

## Configuration

### Step 1: Configure Modules

Edit `config/modules.php` to define local and remote modules:

```php
<?php

return [
    'enabled' => [
        // Format 1: Local module (class only)
        \Metamorphose\Modules\Auth\Module::class,
        
        // Format 2: Local module (with explicit configuration)
        [
            'class' => \Metamorphose\Modules\Stock\Module::class,
            'name' => 'stock', // optional
            'mode' => 'local', // default: 'local'
        ],
        
        // Format 3: Remote module (microservice)
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'remote',
            'endpoint' => getenv('PERMISSION_SERVICE_URL') ?: 'http://permission-service:8000',
            'timeout' => 30, // optional, default: 30 seconds
            'headers' => [ // optional, custom headers
                'X-API-Key' => getenv('PERMISSION_API_KEY'),
            ],
        ],
    ],
];
```

### Step 2: Environment Variables

Configure environment variables for microservice URLs:

```bash
# .env or environment configuration
PERMISSION_SERVICE_URL=http://permission-service:8000
PERMISSION_API_KEY=your-api-key-here
```

## Using ModuleRunner

### Executing Module Actions

To execute a module action, use `ModuleRunner`:

```php
use Metamorphose\Kernel\Module\ModuleRunner;

// In your controller or service
$moduleRunner = $container->get(ModuleRunner::class);

// Execute action locally or remotely (transparent)
$result = $moduleRunner->execute('permission', 'checkPermission', [
    'user_id' => 123,
    'permission' => 'user.create',
]);

// The same code works if the module is local or remote!
```

### Example: Permission Module

**Permission Module (local or remote):**

```php
<?php

namespace Metamorphose\Modules\Permission;

class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Register services
    }

    public function boot(): void
    {
        // Initializations
    }

    public function routes(App $app): void
    {
        // Module routes
    }

    /**
     * Action that can be executed locally or remotely
     */
    public function checkPermission(array $payload): bool
    {
        $userId = $payload['user_id'];
        $permission = $payload['permission'];
        
        // Permission check logic
        // ...
        
        return true; // or false
    }
}
```

**Using the module (without knowing if it's local or remote):**

```php
// In any controller or service
$moduleRunner = $container->get(ModuleRunner::class);

$hasPermission = $moduleRunner->execute('permission', 'checkPermission', [
    'user_id' => $currentUser->getId(),
    'permission' => 'product.create',
]);

if (!$hasPermission) {
    throw new \RuntimeException('Permission denied');
}
```

## Creating a Microservice

### Step 1: Create Microservice Entry Point

The framework already provides the `/module/execute` endpoint that allows executing modules remotely. To create a dedicated microservice, you can:

**Option A: Use the same code base (recommended)**

Create `public/permission-service.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);
Bootstrap\loadRoutes($app, $container);

// The /module/execute endpoint is already available via loadRoutes()
// It will execute only the modules enabled in this service

$app->run();
```

**Option B: Custom entry point**

If you need a specific entry point, you can create:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;
use Metamorphose\Kernel\Module\ModuleLoader;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);

// Load ONLY the Permission module
$moduleClasses = $container->get('config.modules')['enabled'] ?? [];
$loader = new ModuleLoader($container, $app, $moduleClasses);
$loader->load();

// The /module/execute endpoint is already available via Bootstrap\loadRoutes()

$app->run();
```

### Step 2: Configure Microservice

Create `config/modules.php` in the microservice:

```php
<?php

return [
    'enabled' => [
        // Only this module runs in this microservice
        \Metamorphose\Modules\Permission\Module::class,
    ],
];
```

### Step 3: Deploy Microservice

Microservice structure:

```
permission-service/
├── app/
│   └── Modules/
│       └── Permission/  # Only this module
├── config/
│   ├── app.php
│   ├── database.php
│   ├── log.php
│   └── modules.php      # Only Permission enabled
├── public/
│   └── index.php         # Microservice entry point
└── vendor/
```

## How It Works

### Local Execution Flow

1. **Code calls** `ModuleRunner::execute('permission', 'checkPermission', $payload)`
2. **ModuleRunner checks** configuration: module is `local`
3. **LocalModuleExecutor** resolves module via ModuleLoader
4. **Calls method directly**: `$module->checkPermission($payload)`
5. **Returns result** directly

### Remote Execution Flow

1. **Code calls** `ModuleRunner::execute('permission', 'checkPermission', $payload)`
2. **ModuleRunner checks** configuration: module is `remote`
3. **RemoteModuleExecutor** builds standardized payload:
   ```json
   {
     "module": "permission",
     "action": "checkPermission",
     "context": {
       "tenant_id": "123",
       "unit_id": "456",
       "request_id": "abc...",
       "user_id": "789"
     },
     "payload": {
       "user_id": 123,
       "permission": "product.create"
     }
   }
   ```
4. **Sends HTTP POST request** to `{endpoint}/module/execute`
5. **Microservice receives** request at `/module/execute`
6. **ModuleExecuteController** applies context and executes action locally
7. **Returns response**:
   ```json
   {
     "success": true,
     "data": true
   }
   ```
8. **RemoteModuleExecutor** decodes and returns result
9. **Code receives** result as if it were local execution

### Context Preservation

All context information is automatically preserved:

- **tenant_id**: Tenant identifier
- **tenant_code**: Tenant code
- **unit_id**: Unit identifier
- **unit_code**: Unit code
- **request_id**: Unique request ID
- **user_id**: Authenticated user ID

Context is sent in the payload and automatically applied in the microservice.

## Communication Protocol

### Request (Client → Microservice)

```http
POST /module/execute HTTP/1.1
Content-Type: application/json

{
  "module": "permission",
  "action": "checkPermission",
  "context": {
    "tenant_id": "123",
    "tenant_code": "acme",
    "unit_id": "456",
    "unit_code": "warehouse-1",
    "request_id": "abc123...",
    "user_id": "789"
  },
  "payload": {
    "user_id": 123,
    "permission": "product.create"
  }
}
```

### Response (Microservice → Client)

**Success:**
```json
{
  "success": true,
  "data": true
}
```

**Error:**
```json
{
  "success": false,
  "error": "Error message"
}
```

## System Guarantees

✅ **No module needs to know if it's local or remote**
- Module code is identical in both scenarios
- Only configuration defines execution mode

✅ **No controller changes code**
- Controllers use `ModuleRunner` the same way
- No transport logic inside modules

✅ **No business logic changes**
- Module logic remains the same
- Only transport changes (local vs HTTP)

✅ **Total transparency**
- Same code works in monolith and microservices
- Migration is done only by changing configuration

## Complete Example: Permission Module

### 1. Permission Module (code)

```php
<?php

namespace Metamorphose\Modules\Permission;

use Metamorphose\Kernel\Module\ModuleInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Register services
    }

    public function boot(): void
    {
        // Initializations
    }

    public function routes(App $app): void
    {
        // Module routes (if needed)
    }

    /**
     * Checks if user has permission
     * 
     * This action can be executed locally or remotely
     */
    public function checkPermission(array $payload): bool
    {
        $userId = $payload['user_id'] ?? null;
        $permission = $payload['permission'] ?? null;
        
        if (!$userId || !$permission) {
            throw new \RuntimeException('user_id and permission are required');
        }
        
        // Permission check logic
        // ...
        
        return true; // or false
    }

    /**
     * Lists user permissions
     */
    public function getUserPermissions(array $payload): array
    {
        $userId = $payload['user_id'] ?? null;
        
        if (!$userId) {
            throw new \RuntimeException('user_id is required');
        }
        
        // Logic to fetch permissions
        // ...
        
        return ['user.create', 'user.update', 'product.read'];
    }
}
```

### 2. Configuration - Local Mode

```php
// config/modules.php
return [
    'enabled' => [
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'local',
        ],
    ],
];
```

### 3. Configuration - Remote Mode

```php
// config/modules.php
return [
    'enabled' => [
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'remote',
            'endpoint' => getenv('PERMISSION_SERVICE_URL') ?: 'http://permission-service:8000',
            'timeout' => 30,
        ],
    ],
];
```

### 4. Using the Module (same code for both modes)

```php
<?php

namespace Metamorphose\Modules\Product\Controller;

use Metamorphose\Kernel\Module\ModuleRunner;
use Psr\Container\ContainerInterface;

class ProductController
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function create($request, $response)
    {
        $moduleRunner = $this->container->get(ModuleRunner::class);
        
        // Check permission (works local or remote)
        $hasPermission = $moduleRunner->execute('permission', 'checkPermission', [
            'user_id' => $currentUser->getId(),
            'permission' => 'product.create',
        ]);
        
        if (!$hasPermission) {
            return $response->withStatus(403)->withJson(['error' => 'Permission denied']);
        }
        
        // Create product...
    }
}
```

## Benefits

### 1. Code Transparency

Module code doesn't change. The same module works in:
- Monolithic mode
- Microservice mode
- Mixed mode

### 2. Gradual Migration

Migrate modules one at a time:
1. Start with all modules in monolith
2. Extract one module to microservice
3. Update only configuration
4. Repeat for other modules

### 3. Independent Scalability

Scale microservices independently:
- High-traffic modules get more resources
- Low-traffic modules use fewer resources
- Database connections are isolated per service

### 4. Technological Flexibility

Each microservice can:
- Use different PHP versions
- Use different databases
- Deploy independently
- Have its own CI/CD pipeline

## Best Practices

### 1. Service Discovery

Use environment variables or service discovery:

```php
'endpoint' => getenv('PERMISSION_SERVICE_URL') 
    ?: 'http://permission-service.' . getenv('KUBERNETES_NAMESPACE') . '.svc.cluster.local',
```

### 2. Health Checks

Implement health check endpoints in microservices:

```php
$app->get('/health', function ($request, $response) {
    return $response->withJson(['status' => 'ok']);
});
```

### 3. Error Handling

`RemoteModuleExecutor` automatically handles:
- Network errors (timeout, connection refused)
- Response errors (status code != 200)
- Execution errors (response with success=false)

### 4. Timeout Configuration

Configure appropriate timeouts:

```php
[
    'mode' => 'remote',
    'endpoint' => 'http://permission-service:8000',
    'timeout' => 30, // seconds
]
```

### 5. Custom Headers

Add headers for authentication/authorization:

```php
[
    'mode' => 'remote',
    'endpoint' => 'http://permission-service:8000',
    'headers' => [
        'X-API-Key' => getenv('PERMISSION_API_KEY'),
        'Authorization' => 'Bearer ' . getenv('SERVICE_TOKEN'),
    ],
]
```

### 6. Monitoring

Monitor communication between microservices:
- Log all remote requests
- Track response times
- Alert on failures
- Monitor service health

## Migration Strategy

### Phase 1: Preparation

1. Identify modules to extract
2. Ensure modules are self-contained
3. Verify no direct dependencies between modules
4. Test modules independently

### Phase 2: Extraction

1. Create microservice entry point
2. Deploy microservice
3. Update main application configuration (change `mode` to `remote`)
4. Test integration

### Phase 3: Optimization

1. Optimize microservice performance
2. Implement cache if needed
3. Add monitoring and logging
4. Scale as needed

## Example: Complete Configuration

### Main Application (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        // Local modules
        \Metamorphose\Modules\Auth\Module::class,
        \Metamorphose\Modules\UserManagement\Module::class,
        
        // Remote microservices
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'remote',
            'endpoint' => getenv('PERMISSION_SERVICE_URL') ?: 'http://permission-service:8000',
            'timeout' => 30,
        ],
        [
            'class' => \Metamorphose\Modules\Stock\Module::class,
            'name' => 'stock',
            'mode' => 'remote',
            'endpoint' => getenv('STOCK_SERVICE_URL') ?: 'http://stock-service:8000',
            'timeout' => 30,
        ],
    ],
];
```

### Permission Service (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        // Only this module runs in this microservice
        \Metamorphose\Modules\Permission\Module::class,
    ],
];
```

### Docker Compose Example

```yaml
version: '3.8'

services:
  main-app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - PERMISSION_SERVICE_URL=http://permission-service:8000
      - STOCK_SERVICE_URL=http://stock-service:8000
  
  permission-service:
    build: .
    ports:
      - "8001:8000"
    environment:
      - APP_ENV=production
  
  stock-service:
    build: .
    ports:
      - "8002:8000"
    environment:
      - APP_ENV=production
```

## Troubleshooting

### Module Not Found

- Verify module class exists
- Check namespace and autoloading
- Ensure module is in correct directory
- Verify module is enabled in `config/modules.php`

### Connection Refused

- Verify microservice is running
- Verify endpoint is correct
- Check network connectivity
- Review firewall rules

### Context Not Preserved

- Ensure context is sent in payload
- Verify `ModuleExecuteController` applies context correctly
- Check middleware order
- Review request/response handling

### Timeout

- Increase timeout in configuration
- Check microservice performance
- Check network latency
- Consider implementing cache

## Next Steps

- Read about [Modules](modules.md) for module development
- Learn about [Architecture](architecture.md) for system design
- Check [Contexts](contexts.md) for context management
