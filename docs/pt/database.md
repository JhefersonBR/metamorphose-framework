# Banco de Dados

O Metamorphose Framework fornece gerenciamento flexível de conexões de banco de dados com suporte para múltiplos escopos (core, tenant, unit).

## Escopos de Conexão

O framework suporta três escopos de conexão de banco de dados:

### Core (Global)

- Compartilhado entre todos os tenants
- Usado para dados em todo o sistema
- Exemplo: Contas de usuário, configuração do sistema

### Tenant

- Isolado por tenant
- Usado para dados específicos do tenant
- Exemplo: Produtos do tenant, pedidos

### Unit

- Isolado por unit (sub-tenant)
- Usado para dados específicos da unit
- Exemplo: Inventário da unit, configurações locais

## Configuração

Conexões de banco de dados são configuradas em `config/database.php`:

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
        // Estrutura similar
    ],
    'unit' => [
        // Estrutura similar
    ],
];
```

## ConnectionResolver

O `ConnectionResolver` gerencia conexões de banco de dados baseadas no escopo e contexto.

### Uso em Repositories

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

### Métodos

- `resolveCore(): PDO` - Obter conexão de banco de dados core
- `resolveTenant(?string $tenantId = null): PDO` - Obter conexão tenant
- `resolveUnit(?string $unitId = null): PDO` - Obter conexão unit

### Resolução Automática de Contexto

Se você não fornecer IDs de tenant/unit, o resolvedor usa o contexto atual:

```php
// Usa TenantContext automaticamente
$connection = $connectionResolver->resolveTenant();

// Usa UnitContext automaticamente
$connection = $connectionResolver->resolveUnit();
```

## Usando FluentPDO

O framework suporta FluentPDO para uma interface de consulta mais fluente:

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

## Migrações

Migrações são organizadas por escopo em cada módulo:

```
ModuleName/
└── Migrations/
    ├── core/          # Migrações de escopo core
    ├── tenant/        # Migrações de escopo tenant
    └── unit/          # Migrações de escopo unit
```

### Criando Migrações

Crie arquivos de migração no diretório de escopo apropriado:

**Arquivo:** `app/Modules/Product/Migrations/core/0001_create_products_table.php`

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

### Nomenclatura de Migrações

Migrações devem ser nomeadas com um prefixo numérico para ordenação:
- `0001_create_table.php`
- `0002_add_column.php`
- `0003_create_index.php`

### Executando Migrações

```bash
# Executar migrações core
php bin/metamorphose migrate --scope=core

# Executar migrações tenant
php bin/metamorphose migrate --scope=tenant

# Executar migrações unit
php bin/metamorphose migrate --scope=unit
```

### Rastreamento de Migrações

O framework rastreia migrações executadas em uma tabela `migrations`:

```sql
CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Apenas migrações pendentes são executadas.

## Transações

Use transações PDO para integridade de dados:

```php
public function createWithDetails(array $product, array $details): void
{
    $connection = $this->connectionResolver->resolveCore();
    
    $connection->beginTransaction();
    
    try {
        // Inserir produto
        $stmt = $connection->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
        $stmt->execute([$product['name'], $product['price']]);
        $productId = $connection->lastInsertId();
        
        // Inserir detalhes
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

Sempre use prepared statements para prevenir SQL injection:

```php
// Bom
$stmt = $connection->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);

// Ruim (risco de SQL injection)
$connection->query("SELECT * FROM products WHERE id = {$id}");
```

## Connection Pooling

Em runtimes persistentes (Swoole, FrankenPHP), conexões são reutilizadas automaticamente. O `ConnectionResolver` armazena em cache conexões por escopo e contexto.

## Melhores Práticas

1. **Usar escopo apropriado**: Escolha core, tenant ou unit baseado nas necessidades de isolamento de dados
2. **Sempre usar prepared statements**: Prevenir SQL injection
3. **Usar transações**: Garantir consistência de dados
4. **Tratar erros graciosamente**: Capturar exceções e retornar respostas apropriadas
5. **Indexar adequadamente**: Adicionar índices para colunas frequentemente consultadas
6. **Usar migrações**: Nunca modificar schema de banco de dados manualmente
7. **Testar migrações**: Testar migrações em desenvolvimento antes da produção

## Padrões Comuns

### Padrão 1: Consulta com Escopo Tenant

```php
public function findByTenant(string $tenantId): array
{
    $connection = $this->connectionResolver->resolveTenant($tenantId);
    $stmt = $connection->prepare("SELECT * FROM products WHERE tenant_id = ?");
    $stmt->execute([$tenantId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### Padrão 2: Consulta Multi-Escopo

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

### Padrão 3: Usando FluentPDO

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

