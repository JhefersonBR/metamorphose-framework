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

## Bancos de Dados Suportados

O framework suporta os seguintes bancos de dados através do Doctrine DBAL:

- **SQLite** - Banco de dados embutido, ideal para desenvolvimento e testes
- **MySQL / MariaDB** - Bancos relacionais populares
- **PostgreSQL** - Banco relacional avançado e open-source
- **SQL Server** - Banco de dados da Microsoft
- **Oracle** - Banco de dados enterprise da Oracle

## Configuração

Conexões de banco de dados são configuradas em `config/database.php`:

### SQLite

```php
'core' => [
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../storage/database.sqlite',
    'username' => '',
    'password' => '',
    'charset' => 'utf8',
],
```

### MySQL / MariaDB

```php
'core' => [
    'driver' => 'mysql', // ou 'mariadb'
    'host' => getenv('DB_CORE_HOST') ?: 'localhost',
    'port' => getenv('DB_CORE_PORT') ?: 3306,
    'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
    'username' => getenv('DB_CORE_USERNAME') ?: 'root',
    'password' => getenv('DB_CORE_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

### PostgreSQL

```php
'core' => [
    'driver' => 'pgsql', // ou 'postgresql', 'postgres'
    'host' => getenv('DB_CORE_HOST') ?: 'localhost',
    'port' => getenv('DB_CORE_PORT') ?: 5432,
    'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
    'username' => getenv('DB_CORE_USERNAME') ?: 'postgres',
    'password' => getenv('DB_CORE_PASSWORD') ?: '',
    'charset' => 'UTF8',
],
```

### SQL Server

```php
'core' => [
    'driver' => 'sqlsrv', // ou 'mssql', 'sqlserver'
    'host' => getenv('DB_CORE_HOST') ?: 'localhost',
    'port' => getenv('DB_CORE_PORT') ?: 1433,
    'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
    'username' => getenv('DB_CORE_USERNAME') ?: 'sa',
    'password' => getenv('DB_CORE_PASSWORD') ?: '',
    'charset' => 'UTF-8',
],
```

### Oracle

```php
'core' => [
    'driver' => 'oracle', // ou 'oci'
    'host' => getenv('DB_CORE_HOST') ?: 'localhost',
    'port' => getenv('DB_CORE_PORT') ?: 1521,
    'database' => getenv('DB_CORE_DATABASE') ?: 'XE', // SID ou Service Name
    'username' => getenv('DB_CORE_USERNAME') ?: 'system',
    'password' => getenv('DB_CORE_PASSWORD') ?: '',
    'charset' => 'AL32UTF8',
],
```

**Nota para Oracle:**
- Para usar SID: `'database' => 'XE'`
- Para usar Service Name: `'database' => '/service_name'`

### Exemplo Completo

```php
<?php

return [
    'core' => [
        'driver' => getenv('DB_CORE_DRIVER') ?: 'sqlite',
        'host' => getenv('DB_CORE_HOST') ?: 'localhost',
        'port' => getenv('DB_CORE_PORT') ?: 3306,
        'database' => getenv('DB_CORE_DATABASE') ?: __DIR__ . '/../storage/database.sqlite',
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

## DBALConnectionResolver

O `DBALConnectionResolver` gerencia conexões Doctrine DBAL baseadas no escopo e contexto.

### Uso Direto com DBAL

```php
<?php

namespace Metamorphose\Modules\Product\Repository;

use Metamorphose\Kernel\Database\DBALConnectionResolver;
use Doctrine\DBAL\Connection;

class ProductRepository
{
    private DBALConnectionResolver $connectionResolver;

    public function __construct(DBALConnectionResolver $connectionResolver)
    {
        $this->connectionResolver = $connectionResolver;
    }

    public function findAllCore(): array
    {
        $connection = $this->connectionResolver->resolveCore();
        return $connection->fetchAllAssociative("SELECT * FROM products");
    }

    public function findByTenant(string $tenantId): array
    {
        $connection = $this->connectionResolver->resolveTenant($tenantId);
        return $connection->fetchAllAssociative(
            "SELECT * FROM products WHERE tenant_id = ?",
            [$tenantId]
        );
    }
}
```

### Métodos

- `resolveCore(): Connection` - Obter conexão DBAL para escopo core
- `resolveTenant(?string $tenantId = null): Connection` - Obter conexão DBAL para escopo tenant
- `resolveUnit(?string $unitId = null): Connection` - Obter conexão DBAL para escopo unit
- `connection(string $scope): Connection` - Obter conexão por escopo ('core', 'tenant' ou 'unit')

### Resolução Automática de Contexto

Se você não fornecer IDs de tenant/unit, o resolvedor usa o contexto atual:

```php
// Usa TenantContext automaticamente
$connection = $connectionResolver->resolveTenant();

// Usa UnitContext automaticamente
$connection = $connectionResolver->resolveUnit();

// Ou use o método genérico
$connection = $connectionResolver->connection('tenant');
```

## Usando Models

O framework utiliza um sistema de Models com suporte a critérios avançados de pesquisa.

**Importante**: Todas as operações de banco de dados (load, store, delete) exigem uma transação ativa. Se você tentar executar uma operação sem uma transação aberta, uma exceção será lançada.

```php
use Metamorphose\Modules\Product\Model\Product;
use Metamorphose\Kernel\Database\Query\QueryCriteria;
use Metamorphose\Kernel\Database\Query\QueryFilter;
use Metamorphose\Kernel\Database\Transaction;

// Buscar todos os produtos (com transação)
$products = Transaction::run(function () {
    return Product::load(new QueryCriteria());
}, 'tenant');

// Buscar produto por ID (com transação)
$product = Transaction::run(function () {
    return Product::load(1);
}, 'tenant');

// Buscar com filtros (com transação)
$products = Transaction::run(function () {
    $criteria = (new QueryCriteria())
        ->add(new QueryFilter('price', '>', 100))
        ->orderBy('created_at', 'DESC')
        ->limit(10);
    return Product::load($criteria);
}, 'tenant');

// Criar produto (com transação)
$product = Transaction::run(function () {
    $product = new Product();
    $product->fromArray([
        'name' => 'Produto Teste',
        'price' => 99.99,
    ]);
    $product->store();
    return $product;
}, 'tenant');

// Atualizar produto (com transação)
Transaction::run(function () use ($id, $data) {
    $product = Product::load($id);
    if ($product) {
        $product->set('name', $data['name']);
        $product->store();
    }
}, 'tenant');

// Deletar produto (com transação)
Transaction::run(function () use ($id) {
    $product = Product::load($id);
    if ($product) {
        $product->delete();
    }
}, 'tenant');
```

### Alternativa: Usar open() e close()

Você também pode usar `Transaction::open()` e `Transaction::close()` manualmente:

```php
Transaction::open('tenant');

try {
    $product = Product::load(1);
    $product->set('price', 149.99);
    $product->store();
    
    Transaction::close('tenant');
} catch (\Exception $e) {
    Transaction::rollback('tenant');
    throw $e;
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

**Arquivo:** `app/Modules/Product/Migrations/tenant/0001_create_products_table.php`

```php
<?php

use Doctrine\DBAL\Connection;

class Migration0001CreateProductsTable
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $driver = $platform->getName();
        
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                created_at DATETIME,
                updated_at DATETIME
            )";
        } else {
            $sql = "CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        
        $this->connection->executeStatement($sql);
    }

    public function down(): void
    {
        $this->connection->executeStatement("DROP TABLE IF EXISTS products");
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

## AbstractModel

O framework fornece um sistema de Models usando Doctrine DBAL (sem ORM).

### Criando um Model

```php
<?php

namespace Metamorphose\Modules\Product\Model;

use Metamorphose\Kernel\Model\AbstractModel;

class Product extends AbstractModel
{
    protected static string $table = 'products';
    protected static string $primaryKey = 'id';
    protected static string $scope = 'tenant'; // 'core', 'tenant' ou 'unit'
}
```

### Métodos Principais

#### load() - Carregar dados

```php
// Carregar por ID
$product = Product::load(1);

// Carregar com QueryCriteria
use Metamorphose\Kernel\Database\Query\QueryCriteria;
use Metamorphose\Kernel\Database\Query\QueryFilter;

$criteria = (new QueryCriteria())
    ->addFilter('price', '>', 100)
    ->orderBy('created_at', 'DESC')
    ->limit(10);

$products = Product::load($criteria); // Retorna array de Products
```

#### store() - Salvar (insert ou update)

```php
$product = new Product();
$product->fromArray([
    'name' => 'Produto Teste',
    'price' => 99.99,
    'created_at' => date('Y-m-d H:i:s'),
]);
$product->store(); // Insert

$product->set('price', 149.99);
$product->store(); // Update
```

#### delete() - Deletar

```php
$product = Product::load(1);
$product->delete();

// Ou deletar por ID diretamente
$product = new Product();
$product->delete(1);
```

#### Métodos auxiliares

```php
$product = Product::load(1);

// Obter valor
$name = $product->get('name');
$price = $product->get('price', 0); // Com valor padrão

// Definir valor
$product->set('name', 'Novo Nome');

// Verificar se existe
if ($product->has('description')) {
    // ...
}

// Converter para array
$data = $product->toArray();

// Carregar de array
$product->fromArray(['name' => 'Teste', 'price' => 100]);
```

## QueryCriteria e QueryFilter

Sistema avançado de critérios de pesquisa com API fluente.

### QueryFilter

Representa uma condição de filtro:

```php
use Metamorphose\Kernel\Database\Query\QueryFilter;

// Filtro simples
$filter = new QueryFilter('price', '>', 100);

// Filtro com operador lógico
$filter = new QueryFilter('name', 'LIKE', '%test%', 'OR');

// Operadores suportados:
// =, !=, <, >, <=, >=, LIKE, IN, NOT IN, IS NULL, IS NOT NULL, BETWEEN
```

### QueryCriteria

Agrupa filtros e configurações de consulta:

```php
use Metamorphose\Kernel\Database\Query\QueryCriteria;
use Metamorphose\Kernel\Database\Query\QueryFilter;

$criteria = (new QueryCriteria())
    ->addFilter('price', '>=', 50)
    ->addFilter('price', '<=', 200)
    ->addFilter('status', '=', 'active')
    ->orderBy('created_at', 'DESC')
    ->orderBy('name', 'ASC')
    ->limit(20)
    ->offset(0)
    ->groupBy('category_id');

$products = Product::load($criteria);
```

### Exemplos Avançados

```php
// Buscar produtos com preço entre valores
$criteria = (new QueryCriteria())
    ->addFilter('price', 'BETWEEN', [100, 500])
    ->orderBy('price', 'ASC');

// Buscar produtos em uma lista de IDs
$criteria = (new QueryCriteria())
    ->addFilter('id', 'IN', [1, 5, 10, 15]);

// Buscar produtos sem descrição
$criteria = (new QueryCriteria())
    ->addFilter('description', 'IS NULL');

// Combinação AND/OR
$criteria = (new QueryCriteria())
    ->addFilter('price', '>', 100, 'AND')
    ->addFilter('name', 'LIKE', '%test%', 'OR')
    ->addFilter('status', '=', 'active', 'AND');
```

## Transaction - Gerenciamento de Transações

A classe `Transaction` fornece um gerenciamento de transações onde a transação só faz commit quando é fechada (`close()`), e suporta transações aninhadas.

### Características

- **Commit automático**: O commit só acontece quando `close()` é chamado
- **Transações aninhadas**: Suporta múltiplas transações do mesmo escopo
- **Rollback automático**: Em caso de exceção ou erro no commit
- **Múltiplos escopos**: Suporta `core`, `tenant` e `unit`

### Métodos Disponíveis

#### open() - Abrir Transação

```php
use Metamorphose\Kernel\Database\Transaction;

// Abre uma transação para o escopo tenant
Transaction::open('tenant');

// Operações dentro da transação
$product1 = new Product();
$product1->fromArray(['name' => 'Produto 1', 'price' => 99.99]);
$product1->store();

$product2 = new Product();
$product2->fromArray(['name' => 'Produto 2', 'price' => 149.99]);
$product2->store();

// Commit acontece automaticamente aqui
Transaction::close('tenant');
```

#### close() - Fechar Transação (Commit Automático)

```php
Transaction::open('tenant');
// ... operações ...
Transaction::close('tenant'); // Commit automático
```

**Importante**: Se não houver transação aberta, `close()` lança uma exceção.

#### rollback() - Fazer Rollback

```php
Transaction::open('tenant');

try {
    // ... operações ...
    Transaction::close('tenant');
} catch (\Exception $e) {
    // Rollback manual em caso de erro
    Transaction::rollback('tenant');
    throw $e;
}
```

**Importante**: Se não houver transação aberta, `rollback()` lança uma exceção.

#### active() - Verificar se há Transação Ativa

```php
if (Transaction::active('tenant')) {
    // Há uma transação ativa para o escopo tenant
}
```

#### getConnection() - Obter Conexão da Transação

```php
$connection = Transaction::getConnection('tenant');
if ($connection !== null) {
    // Usar a conexão da transação ativa
}
```

#### run() - Executar Callback em Transação (Recomendado)

O método `run()` é a forma mais segura de usar transações, pois garante commit ou rollback automático:

```php
use Metamorphose\Kernel\Database\Transaction;
use Metamorphose\Modules\Example\Model\Product;

$products = Transaction::run(function ($connection) {
    $createdProducts = [];
    
    // Criar múltiplos produtos
    foreach ($items as $item) {
        $product = new Product();
        $product->fromArray([
            'name' => $item['name'],
            'price' => $item['price'],
        ]);
        $product->store();
        $createdProducts[] = $product->toArray();
    }
    
    return $createdProducts;
}, 'tenant');

// Se qualquer operação falhar, tudo é revertido automaticamente
```

### Transações Aninhadas

A classe suporta transações aninhadas do mesmo escopo:

```php
Transaction::open('tenant'); // Nível 1
// ... operações ...

Transaction::open('tenant'); // Nível 2 (aninhada)
// ... mais operações ...
Transaction::close('tenant'); // Decrementa para nível 1

// ... mais operações ...
Transaction::close('tenant'); // Commit real aqui (nível 0)
```

### Exemplo Completo

```php
use Metamorphose\Kernel\Database\Transaction;
use Metamorphose\Modules\Example\Model\Product;

class OrderService
{
    public function createOrder(array $orderData, array $items): void
    {
        Transaction::run(function ($connection) use ($orderData, $items) {
            // Criar pedido
            $order = new Order();
            $order->fromArray([
                'customer_id' => $orderData['customer_id'],
                'total' => $orderData['total'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $order->store();
            $orderId = $order->get('id');

            // Criar itens do pedido
            foreach ($items as $item) {
                $orderItem = new OrderItem();
                $orderItem->fromArray([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
                $orderItem->store();

                // Atualizar estoque usando conexão direta
                $connection->executeStatement(
                    "UPDATE products SET stock = stock - :quantity WHERE id = :id",
                    ['quantity' => $item['quantity'], 'id' => $item['product_id']]
                );
            }

            return $orderId;
        }, 'tenant');
    }
}
```

### Tratamento de Erros

```php
try {
    Transaction::open('tenant');
    
    // Operações que podem falhar
    $product->store();
    $inventory->update();
    
    Transaction::close('tenant');
} catch (\RuntimeException $e) {
    // Erro ao fechar sem ter aberto
    // ou erro no commit
    Transaction::rollback('tenant');
    throw $e;
} catch (\Exception $e) {
    // Outros erros
    Transaction::rollback('tenant');
    throw $e;
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

### Padrão 3: Usando Models com QueryCriteria

```php
use Metamorphose\Modules\Product\Model\Product;
use Metamorphose\Kernel\Database\Query\QueryCriteria;
use Metamorphose\Kernel\Database\Query\QueryFilter;

public function search(string $query): array
{
    $criteria = (new QueryCriteria())
        ->add(new QueryFilter('name', 'LIKE', "%{$query}%"))
        ->orderBy('name');
    
    return Product::load($criteria);
}
```

