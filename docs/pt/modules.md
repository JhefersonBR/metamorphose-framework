# Módulos

Módulos são os blocos de construção do Metamorphose Framework. Eles encapsulam funcionalidade e podem ser facilmente adicionados ou removidos da sua aplicação.

## Estrutura de um Módulo

Um módulo segue esta estrutura:

```
ModuleName/
├── Module.php              # Classe principal do módulo
├── Routes.php              # Arquivo de rotas (opcional)
├── config.php              # Configuração do módulo
├── Controller/             # Controllers HTTP
├── Service/                # Serviços de lógica de negócio
├── Repository/            # Camada de acesso a dados
├── Entity/                 # Entidades de domínio
└── Migrations/             # Migrações de banco de dados
    ├── core/               # Migrações de escopo core
    ├── tenant/             # Migrações de escopo tenant
    └── unit/               # Migrações de escopo unit
```

## Criando um Módulo

### Usando CLI

A maneira mais fácil de criar um módulo é usando o CLI:

```bash
php bin/metamorphose module:make ProductCatalog
```

Isso cria a estrutura completa do módulo com:
- `Module.php` com implementação básica
- `Routes.php` placeholder
- `config.php` com valores padrão
- `Controller/ProductCatalogController.php` com código de exemplo
- Diretórios vazios para Service, Repository, Entity
- Diretórios de migração para todos os escopos

### Criação Manual

Você também pode criar um módulo manualmente:

1. Criar a estrutura de diretórios
2. Criar `Module.php` implementando `ModuleInterface`
3. Registrar o módulo em `config/modules.php`

## Interface do Módulo

Cada módulo deve implementar `ModuleInterface`:

```php
<?php

namespace Metamorphose\Modules\SeuModulo;

use Metamorphose\Kernel\Module\ModuleInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Registrar serviços aqui
    }

    public function boot(): void
    {
        // Inicializar após o registro
    }

    public function routes(App $app): void
    {
        // Registrar rotas aqui
    }
}
```

## Método Register

O método `register()` é chamado primeiro e é usado para registrar serviços no container:

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

## Método Boot

O método `boot()` é chamado após todos os módulos serem registrados. Use-o para inicialização que depende de outros serviços:

```php
public function boot(): void
{
    // Exemplo: Inicializar cache, conectar a serviços externos, etc.
    $cache = $this->container->get(CacheInterface::class);
    $cache->warm();
}
```

## Método Routes

O método `routes()` registra rotas HTTP:

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

## Configuração do Módulo

Cada módulo pode ter seu próprio arquivo de configuração (`config.php`):

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

Acesse a configuração no seu módulo:

```php
$config = require __DIR__ . '/config.php';
$itemsPerPage = $config['settings']['items_per_page'];
```

## Controllers

Controllers tratam requisições HTTP e retornam respostas:

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

Services contêm lógica de negócio:

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

Repositories lidam com acesso a dados:

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

## Habilitando Módulos

Para habilitar um módulo, adicione-o em `config/modules.php`:

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

Módulos são carregados na ordem em que aparecem neste array.

## Dependências de Módulos

Módulos devem ser independentes. Se você precisar compartilhar funcionalidade:

1. **Criar um serviço compartilhado**: Registre-o no container principal
2. **Usar eventos** (recurso futuro): Módulos podem se comunicar via eventos
3. **Extrair código comum**: Criar um módulo ou biblioteca compartilhada

## Melhores Práticas

1. **Manter módulos focados**: Cada módulo deve ter uma única responsabilidade
2. **Usar injeção de dependência**: Não criar dependências diretamente
3. **Respeitar contextos**: Usar TenantContext e UnitContext adequadamente
4. **Tratar erros graciosamente**: Retornar códigos de status HTTP apropriados
5. **Documentar seu módulo**: Adicionar comentários explicando lógica complexa
6. **Testar seu módulo**: Escrever testes para controllers, services e repositories

## Exemplo: Módulo Completo

Veja `app/Modules/Example/` para um exemplo completo e funcional de um módulo.

