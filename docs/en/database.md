# Database

Metamorphose Framework provides flexible database connection management with support for multiple scopes (core, tenant, unit).

## Connection Scopes

The framework supports three database connection scopes:

### Core (Global)

- Shared across all tenants
- Used for system-wide data
- Example: User accounts, system configuration

### Tenant

- Isolated per tenant
- Used for tenant-specific data
- Example: Tenant products, orders

### Unit

- Isolated per unit (sub-tenant)
- Used for unit-specific data
- Example: Unit inventory, local settings

## Configuration

Database connections are configured in `config/database.php`:

```php
<?php

return [
    'core' => [
        'driver' => 'mysql',
        'host' => getenv('DB_CORE_HOST') ?: 'localhost',
        'port' => getenv('DB_CORE_PORT') ?: 3306,
        'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
        'username' => getenv('DB_CORE_USERNAME') ?: 'root',
        'password' => getenv('DB_CORE_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'tenant' => [
        // Similar structure
    ],
    'unit' => [
        // Similar structure
    ],
];
```

## ConnectionResolver

The `ConnectionResolver` manages database connections based on scope and context.

### Usage in Repositories

```php
<?php

namespace Metamorphose\Modules\Product\Repository;

use Metamorphose\Kernel\Database\ConnectionResolverInterface;
use PDO;

class ProductRepository
{
    private ConnectionResolverInterface $connectionResolver;

    public function __construct(ConnectionResolverInterface $connectionResolver)
    {
        $this->connectionResolver = $connectionResolver;
    }

    public function findAllCore(): array
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

    public function findByUnit(string $unitId): array
    {
        $connection = $this->connectionResolver->resolveUnit($unitId);
        $stmt = $connection->prepare("SELECT * FROM products WHERE unit_id = ?");
        $stmt->execute([$unitId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### Methods

- `resolveCore(): PDO` - Get core database connection
- `resolveTenant(?string $tenantId = null): PDO` - Get tenant connection
- `resolveUnit(?string $unitId = null): PDO` - Get unit connection

### Automatic Context Resolution

If you don't provide tenant/unit IDs, the resolver uses the current context:

```php
// Uses TenantContext automatically
$connection = $connectionResolver->resolveTenant();

// Uses UnitContext automatically
$connection = $connectionResolver->resolveUnit();
```

## Using FluentPDO

The framework supports FluentPDO for a more fluent query interface:

```php
use Envms\FluentPDO\Query;

class ProductRepository
{
    private ConnectionResolverInterface $connectionResolver;

    public function findAll(): array
    {
        $pdo = $this->connectionResolver->resolveCore();
        $query = new Query($pdo);
        
        return $query->from('products')
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $pdo = $this->connectionResolver->resolveCore();
        $query = new Query($pdo);
        
        return $query->from('products')
            ->where('id', $id)
            ->fetch();
    }
}
```

## Migrations

Migrations are organized by scope in each module:

```
ModuleName/
└── Migrations/
    ├── core/          # Core scope migrations
    ├── tenant/        # Tenant scope migrations
    └── unit/          # Unit scope migrations
```

### Creating Migrations

Create migration files in the appropriate scope directory:

**File:** `app/Modules/Product/Migrations/core/0001_create_products_table.php`

```php
<?php

class CreateProductsTable
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->connection->exec($sql);
    }
}
```

### Migration Naming

Migrations should be named with a numeric prefix for ordering:
- `0001_create_table.php`
- `0002_add_column.php`
- `0003_create_index.php`

### Running Migrations

```bash
# Run core migrations
php bin/metamorphose migrate --scope=core

# Run tenant migrations
php bin/metamorphose migrate --scope=tenant

# Run unit migrations
php bin/metamorphose migrate --scope=unit
```

### Migration Tracking

The framework tracks executed migrations in a `migrations` table:

```sql
CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Only pending migrations are executed.

## Transactions

Use PDO transactions for data integrity:

```php
public function createWithDetails(array $product, array $details): void
{
    $connection = $this->connectionResolver->resolveCore();
    
    $connection->beginTransaction();
    
    try {
        // Insert product
        $stmt = $connection->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
        $stmt->execute([$product['name'], $product['price']]);
        $productId = $connection->lastInsertId();
        
        // Insert details
        $stmt = $connection->prepare("INSERT INTO product_details (product_id, detail) VALUES (?, ?)");
        foreach ($details as $detail) {
            $stmt->execute([$productId, $detail]);
        }
        
        $connection->commit();
    } catch (\Exception $e) {
        $connection->rollBack();
        throw $e;
    }
}
```

## Prepared Statements

Always use prepared statements to prevent SQL injection:

```php
// Good
$stmt = $connection->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);

// Bad (SQL injection risk)
$connection->query("SELECT * FROM products WHERE id = {$id}");
```

## Connection Pooling

In persistent runtimes (Swoole, FrankenPHP), connections are reused automatically. The `ConnectionResolver` caches connections per scope and context.

## Best Practices

1. **Use appropriate scope**: Choose core, tenant, or unit based on data isolation needs
2. **Always use prepared statements**: Prevent SQL injection
3. **Use transactions**: Ensure data consistency
4. **Handle errors gracefully**: Catch exceptions and return proper responses
5. **Index appropriately**: Add indexes for frequently queried columns
6. **Use migrations**: Never modify database schema manually
7. **Test migrations**: Test migrations in development before production

## Common Patterns

### Pattern 1: Tenant-Scoped Query

```php
public function findByTenant(string $tenantId): array
{
    $connection = $this->connectionResolver->resolveTenant($tenantId);
    $stmt = $connection->prepare("SELECT * FROM products WHERE tenant_id = ?");
    $stmt->execute([$tenantId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### Pattern 2: Multi-Scope Query

```php
public function findAllForContext(): array
{
    if ($this->unitContext->hasUnit()) {
        $connection = $this->connectionResolver->resolveUnit();
        $stmt = $connection->prepare("SELECT * FROM products WHERE unit_id = ?");
        $stmt->execute([$this->unitContext->getUnitId()]);
    } elseif ($this->tenantContext->hasTenant()) {
        $connection = $this->connectionResolver->resolveTenant();
        $stmt = $connection->prepare("SELECT * FROM products WHERE tenant_id = ?");
        $stmt->execute([$this->tenantContext->getTenantId()]);
    } else {
        $connection = $this->connectionResolver->resolveCore();
        $stmt = $connection->query("SELECT * FROM products");
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### Pattern 3: Using FluentPDO

```php
public function search(string $query): array
{
    $pdo = $this->connectionResolver->resolveCore();
    $fluent = new Query($pdo);
    
    return $fluent->from('products')
        ->where('name LIKE ?', "%{$query}%")
        ->orderBy('name')
        ->fetchAll();
}
```

