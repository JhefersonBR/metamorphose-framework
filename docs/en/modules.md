# Modules

Modules are the building blocks of Metamorphose Framework. They encapsulate functionality and can be easily added or removed from your application.

## Module Structure

A module follows this structure:

```
ModuleName/
├── Module.php              # Main module class
├── Routes.php              # Routes file (optional)
├── config.php              # Module configuration
├── Controller/             # HTTP controllers
├── Service/                # Business logic services
├── Repository/             # Data access layer
├── Entity/                 # Domain entities
└── Migrations/             # Database migrations
    ├── core/               # Core scope migrations
    ├── tenant/             # Tenant scope migrations
    └── unit/               # Unit scope migrations
```

## Creating a Module

### Using CLI

The easiest way to create a module is using the CLI:

```bash
php bin/metamorphose module:make ProductCatalog
```

This creates the complete module structure with:
- `Module.php` with basic implementation
- `Routes.php` placeholder
- `config.php` with default values
- `Controller/ProductCatalogController.php` with example code
- Empty directories for Service, Repository, Entity
- Migration directories for all scopes

### Manual Creation

You can also create a module manually:

1. Create the directory structure
2. Create `Module.php` implementing `ModuleInterface`
3. Register the module in `config/modules.php`

## Module Interface

Every module must implement `ModuleInterface`:

```php
<?php

namespace Metamorphose\Modules\YourModule;

use Metamorphose\Kernel\Module\ModuleInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Register services here
    }

    public function boot(): void
    {
        // Initialize after registration
    }

    public function routes(App $app): void
    {
        // Register routes here
    }
}
```

## Register Method

The `register()` method is called first and is used to register services in the container:

```php
public function register(ContainerInterface $container): void
{
    $container->set(
        \Metamorphose\Modules\ProductCatalog\Repository\ProductRepository::class,
        function (ContainerInterface $c) {
            return new \Metamorphose\Modules\ProductCatalog\Repository\ProductRepository(
                $c->get(\Metamorphose\Kernel\Database\ConnectionResolverInterface::class)
            );
        }
    );
    
    $container->set(
        \Metamorphose\Modules\ProductCatalog\Service\ProductService::class,
        function (ContainerInterface $c) {
            return new \Metamorphose\Modules\ProductCatalog\Service\ProductService(
                $c->get(\Metamorphose\Modules\ProductCatalog\Repository\ProductRepository::class)
            );
        }
    );
}
```

## Boot Method

The `boot()` method is called after all modules are registered. Use it for initialization that depends on other services:

```php
public function boot(): void
{
    // Example: Initialize cache, connect to external services, etc.
    $cache = $this->container->get(CacheInterface::class);
    $cache->warm();
}
```

## Routes Method

The `routes()` method registers HTTP routes:

```php
public function routes(App $app): void
{
    $app->get('/products', \Metamorphose\Modules\ProductCatalog\Controller\ProductController::class . ':index');
    $app->get('/products/{id}', \Metamorphose\Modules\ProductCatalog\Controller\ProductController::class . ':show');
    $app->post('/products', \Metamorphose\Modules\ProductCatalog\Controller\ProductController::class . ':create');
    $app->put('/products/{id}', \Metamorphose\Modules\ProductCatalog\Controller\ProductController::class . ':update');
    $app->delete('/products/{id}', \Metamorphose\Modules\ProductCatalog\Controller\ProductController::class . ':delete');
}
```

## Module Configuration

Each module can have its own configuration file (`config.php`):

```php
<?php

return [
    'name' => 'Product Catalog Module',
    'version' => '1.0.0',
    'enabled' => true,
    'settings' => [
        'items_per_page' => 20,
        'enable_cache' => true,
        'cache_ttl' => 3600,
    ],
];
```

Access configuration in your module:

```php
$config = require __DIR__ . '/config.php';
$itemsPerPage = $config['settings']['items_per_page'];
```

## Controllers

Controllers handle HTTP requests and return responses:

```php
<?php

namespace Metamorphose\Modules\ProductCatalog\Controller;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController
{
    private RequestContext $requestContext;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;
    private ProductService $productService;

    public function __construct(
        RequestContext $requestContext,
        TenantContext $tenantContext,
        UnitContext $unitContext,
        ProductService $productService
    ) {
        $this->requestContext = $requestContext;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
        $this->productService = $productService;
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $products = $this->productService->getAll();
        
        $response->getBody()->write(json_encode($products, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

## Services

Services contain business logic:

```php
<?php

namespace Metamorphose\Modules\ProductCatalog\Service;

use Metamorphose\Modules\ProductCatalog\Repository\ProductRepository;

class ProductService
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }
}
```

## Repositories

Repositories handle data access:

```php
<?php

namespace Metamorphose\Modules\ProductCatalog\Repository;

use Metamorphose\Kernel\Database\ConnectionResolverInterface;
use PDO;

class ProductRepository
{
    private ConnectionResolverInterface $connectionResolver;

    public function __construct(ConnectionResolverInterface $connectionResolver)
    {
        $this->connectionResolver = $connectionResolver;
    }

    public function findAll(): array
    {
        $connection = $this->connectionResolver->resolveCore();
        $stmt = $connection->query("SELECT * FROM products");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByTenant(string $tenantId): array
    {
        $connection = $this->connectionResolver->resolveTenant($tenantId);
        $stmt = $connection->prepare("SELECT * FROM products WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

## Enabling Modules

To enable a module, add it to `config/modules.php`:

```php
<?php

return [
    'enabled' => [
        \Metamorphose\Modules\Example\Module::class,
        \Metamorphose\Modules\ProductCatalog\Module::class,
        \Metamorphose\Modules\Blog\Module::class,
    ],
];
```

Modules are loaded in the order they appear in this array.

## Module Dependencies

Modules should be independent. If you need to share functionality:

1. **Create a shared service**: Register it in the core container
2. **Use events** (future feature): Modules can communicate via events
3. **Extract common code**: Create a shared module or library

## Best Practices

1. **Keep modules focused**: Each module should have a single responsibility
2. **Use dependency injection**: Don't create dependencies directly
3. **Respect contexts**: Use TenantContext and UnitContext appropriately
4. **Handle errors gracefully**: Return proper HTTP status codes
5. **Document your module**: Add comments explaining complex logic
6. **Test your module**: Write tests for controllers, services, and repositories

## Example: Complete Module

See `app/Modules/Example/` for a complete working example of a module.

