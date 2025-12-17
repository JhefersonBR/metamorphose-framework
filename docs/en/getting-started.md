# Getting Started

This guide will help you get started with Metamorphose Framework by creating your first module and understanding the basic concepts.

## Understanding the Structure

```
metamorphose-framework/
├── app/
│   ├── Bootstrap/          # Application bootstrap files
│   ├── Kernel/              # Core framework components
│   ├── Modules/             # Your application modules
│   └── CLI/                 # Command-line interface
├── config/                  # Configuration files
├── public/                  # Web server entry point
└── bin/                     # CLI executables
```

## Your First Request

After installation, make a request to the Example module:

```bash
curl http://localhost/example
```

You should receive a JSON response:

```json
{
    "message": "Hello from Example Module!",
    "request_id": "a1b2c3d4e5f6...",
    "tenant_id": null,
    "unit_id": null
}
```

## Creating Your First Module

### Step 1: Create the Module

```bash
php bin/metamorphose module:make Blog
```

This creates a complete module structure:

```
app/Modules/Blog/
├── Module.php
├── Routes.php
├── config.php
├── Controller/
│   └── BlogController.php
├── Service/
├── Repository/
├── Entity/
└── Migrations/
    ├── core/
    ├── tenant/
    └── unit/
```

### Step 2: Register the Module

Edit `config/modules.php`:

```php
<?php

return [
    'enabled' => [
        \Metamorphose\Modules\Example\Module::class,
        \Metamorphose\Modules\Blog\Module::class,  // Add your module
    ],
];
```

### Step 3: Define Routes

Edit `app/Modules/Blog/Module.php`:

```php
public function routes(App $app): void
{
    $app->get('/blog', \Metamorphose\Modules\Blog\Controller\BlogController::class . ':index');
    $app->get('/blog/{id}', \Metamorphose\Modules\Blog\Controller\BlogController::class . ':show');
}
```

### Step 4: Implement Controller Logic

Edit `app/Modules/Blog/Controller/BlogController.php`:

```php
public function index(
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    $data = [
        'posts' => [
            ['id' => 1, 'title' => 'First Post'],
            ['id' => 2, 'title' => 'Second Post'],
        ],
        'request_id' => $this->requestContext->getRequestId(),
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
}
```

## Understanding Contexts

Metamorphose Framework uses explicit contexts to manage multi-tenant data:

### Request Context

Automatically created for each request, contains:
- `request_id`: Unique identifier for the request
- `user_id`: Current user ID (if authenticated)
- Request metadata

### Tenant Context

Populated via headers or query parameters:
- `X-Tenant-ID` header or `tenant_id` query parameter
- `X-Tenant-Code` header (optional)

### Unit Context

Populated via headers or query parameters:
- `X-Unit-ID` header or `unit_id` query parameter
- `X-Unit-Code` header (optional)

### Using Contexts in Controllers

```php
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
    if ($this->tenantContext->hasTenant()) {
        $tenantId = $this->tenantContext->getTenantId();
        // Use tenant-specific data
    }
    
    // ...
}
```

## Working with Database

### Creating a Repository

Create `app/Modules/Blog/Repository/BlogRepository.php`:

```php
<?php

namespace Metamorphose\Modules\Blog\Repository;

use Metamorphose\Kernel\Database\ConnectionResolverInterface;
use PDO;

class BlogRepository
{
    private ConnectionResolverInterface $connectionResolver;

    public function __construct(ConnectionResolverInterface $connectionResolver)
    {
        $this->connectionResolver = $connectionResolver;
    }

    public function findAll(): array
    {
        $connection = $this->connectionResolver->resolveCore();
        $stmt = $connection->query("SELECT * FROM blog_posts");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByTenant(string $tenantId): array
    {
        $connection = $this->connectionResolver->resolveTenant($tenantId);
        $stmt = $connection->query("SELECT * FROM blog_posts WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### Registering Services

In your module's `register()` method:

```php
public function register(ContainerInterface $container): void
{
    $container->set(
        \Metamorphose\Modules\Blog\Repository\BlogRepository::class,
        function (ContainerInterface $c) {
            return new \Metamorphose\Modules\Blog\Repository\BlogRepository(
                $c->get(\Metamorphose\Kernel\Database\ConnectionResolverInterface::class)
            );
        }
    );
}
```

## Creating Migrations

Create a migration file in `app/Modules/Blog/Migrations/core/0001_create_blog_posts.php`:

```php
<?php

class CreateBlogPosts
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->connection->exec($sql);
    }
}
```

Run the migration:

```bash
php bin/metamorphose migrate --scope=core
```

## Next Steps

- Learn about [Architecture](architecture.md)
- Explore [Modules](modules.md) in detail
- Understand [Contexts](contexts.md)
- Read about [Database](database.md) connections

