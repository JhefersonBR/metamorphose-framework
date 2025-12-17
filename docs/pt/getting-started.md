# Primeiros Passos

Este guia ajudará você a começar com o Metamorphose Framework criando seu primeiro módulo e entendendo os conceitos básicos.

## Entendendo a Estrutura

```
metamorphose-framework/
├── app/
│   ├── Bootstrap/          # Arquivos de bootstrap da aplicação
│   ├── Kernel/             # Componentes principais do framework
│   ├── Modules/             # Seus módulos de aplicação
│   └── CLI/                 # Interface de linha de comando
├── config/                  # Arquivos de configuração
├── public/                  # Ponto de entrada do servidor web
└── bin/                     # Executáveis CLI
```

## Sua Primeira Requisição

Após a instalação, faça uma requisição para o módulo Example:

```bash
curl http://localhost/example
```

Você deve receber uma resposta JSON:

```json
{
    "message": "Hello from Example Module!",
    "request_id": "a1b2c3d4e5f6...",
    "tenant_id": null,
    "unit_id": null
}
```

## Criando Seu Primeiro Módulo

### Passo 1: Criar o Módulo

```bash
php bin/metamorphose module:make Blog
```

Isso cria uma estrutura completa de módulo:

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

### Passo 2: Registrar o Módulo

Edite `config/modules.php`:

```php
<?php

return [
    'enabled' => [
        \Metamorphose\Modules\Example\Module::class,
        \Metamorphose\Modules\Blog\Module::class,  // Adicione seu módulo
    ],
];
```

### Passo 3: Definir Rotas

Edite `app/Modules/Blog/Module.php`:

```php
public function routes(App $app): void
{
    $app->get('/blog', \Metamorphose\Modules\Blog\Controller\BlogController::class . ':index');
    $app->get('/blog/{id}', \Metamorphose\Modules\Blog\Controller\BlogController::class . ':show');
}
```

### Passo 4: Implementar Lógica do Controller

Edite `app/Modules/Blog/Controller/BlogController.php`:

```php
public function index(
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    $data = [
        'posts' => [
            ['id' => 1, 'title' => 'Primeiro Post'],
            ['id' => 2, 'title' => 'Segundo Post'],
        ],
        'request_id' => $this->requestContext->getRequestId(),
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
}
```

## Entendendo Contextos

O Metamorphose Framework usa contextos explícitos para gerenciar dados multi-tenant:

### Request Context

Criado automaticamente para cada requisição, contém:
- `request_id`: Identificador único para a requisição
- `user_id`: ID do usuário atual (se autenticado)
- Metadados da requisição

### Tenant Context

Preenchido via headers ou parâmetros de query:
- Header `X-Tenant-ID` ou parâmetro `tenant_id`
- Header `X-Tenant-Code` (opcional)

### Unit Context

Preenchido via headers ou parâmetros de query:
- Header `X-Unit-ID` ou parâmetro `unit_id`
- Header `X-Unit-Code` (opcional)

### Usando Contextos em Controllers

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
        // Usar dados específicos do tenant
    }
    
    // ...
}
```

## Trabalhando com Banco de Dados

### Criando um Repository

Crie `app/Modules/Blog/Repository/BlogRepository.php`:

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
        $stmt = $connection->prepare("SELECT * FROM blog_posts WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### Registrando Serviços

No método `register()` do seu módulo:

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

## Criando Migrações

Crie um arquivo de migração em `app/Modules/Blog/Migrations/core/0001_create_blog_posts.php`:

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

Execute a migração:

```bash
php bin/metamorphose migrate --scope=core
```

## Próximos Passos

- Aprenda sobre [Arquitetura](architecture.md)
- Explore [Módulos](modules.md) em detalhes
- Entenda [Contextos](contexts.md)
- Leia sobre conexões de [Banco de Dados](database.md)

