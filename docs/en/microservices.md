# Microservices and Remote Modules

Metamorphose Framework is designed to support both monolithic and microservices architectures. You can easily extract modules into separate microservices without changing the module code itself.

## Overview

The framework allows you to:
- Run all modules in a single monolithic application
- Extract specific modules to run as separate microservices
- Mix local and remote modules transparently
- Migrate modules gradually from monolith to microservices

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
│  └──────────┘  └──────────┘   │
│         │              │        │
│         ▼              ▼        │
│  ┌──────────┐  ┌──────────┐   │
│  │ Product   │  │ Order    │   │
│  │ Service   │  │ Service  │   │
│  │ (Remote)  │  │ (Remote) │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

## Configuration

### Step 1: Configure Remote Modules

Edit `config/modules.php` to define remote modules:

```php
<?php

return [
    'enabled' => [
        // Local modules (run in monolith)
        \Metamorphose\Modules\Example\Module::class,
        \Metamorphose\Modules\Auth\Module::class,
        
        // Remote modules (microservices)
        [
            'type' => 'remote',
            'name' => 'ProductCatalog',
            'base_url' => getenv('PRODUCT_SERVICE_URL') ?: 'http://product-service:8000',
            'routes_prefix' => '/products', // Optional route prefix
        ],
        [
            'type' => 'remote',
            'name' => 'OrderManagement',
            'base_url' => getenv('ORDER_SERVICE_URL') ?: 'http://order-service:8000',
            'routes_prefix' => '/orders',
        ],
    ],
];
```

### Step 2: Environment Variables

Set environment variables for microservice URLs:

```bash
# .env or environment configuration
PRODUCT_SERVICE_URL=http://product-service:8000
ORDER_SERVICE_URL=http://order-service:8000
```

## Creating a Microservice

### Step 1: Create Microservice Entry Point

Create `public/product-service.php` (or deploy to separate server):

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);

// Load ONLY the ProductCatalog module
$loader = new \Metamorphose\Kernel\Module\ModuleLoader(
    $container,
    $app,
    [\Metamorphose\Modules\ProductCatalog\Module::class]
);
$loader->load();

$app->run();
```

### Step 2: Configure Microservice

Create `config/modules.php` in the microservice:

```php
<?php

return [
    'enabled' => [
        // Only this module runs in this microservice
        \Metamorphose\Modules\ProductCatalog\Module::class,
    ],
];
```

### Step 3: Deploy Microservice

The microservice structure:

```
product-service/
├── app/
│   └── Modules/
│       └── ProductCatalog/  # Only this module
├── config/
│   ├── app.php
│   ├── database.php
│   ├── log.php
│   └── modules.php          # Only ProductCatalog enabled
├── public/
│   └── index.php             # Microservice entry point
└── vendor/
```

## How It Works

### Request Flow

1. **Request arrives** at main application
2. **Route matches** remote module prefix (e.g., `/products/*`)
3. **Proxy middleware** forwards request to microservice
4. **Context headers** (`X-Tenant-ID`, `X-Unit-ID`) are preserved
5. **Response** is returned to client

### Context Preservation

All context information is automatically forwarded:

- `X-Tenant-ID`: Tenant identifier
- `X-Unit-ID`: Unit identifier
- `X-Tenant-Code`: Tenant code
- `X-Unit-Code`: Unit code
- `Authorization`: Authentication tokens
- Custom headers

## Implementation Details

### RemoteModule Class

The framework includes a `RemoteModule` class that handles proxying:

```php
<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Slim\App;

class RemoteModule implements ModuleInterface
{
    private string $name;
    private string $baseUrl;
    private ?string $routesPrefix;
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;

    public function __construct(
        array $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory
    ) {
        $this->name = $config['name'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->routesPrefix = $config['routes_prefix'] ?? null;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    public function register(ContainerInterface $container): void
    {
        // No local services to register
    }

    public function boot(): void
    {
        // No local initialization needed
    }

    public function routes(App $app): void
    {
        $prefix = $this->routesPrefix ?? '';
        
        // Proxy all routes to microservice
        $app->any($prefix . '/{path:.*}', function ($request, $response, $args) {
            // Forward request to microservice
            // Preserve all headers and context
            // Return response
        });
    }
}
```

### ModuleLoader Enhancement

The `ModuleLoader` detects remote modules:

```php
foreach ($moduleConfigs as $moduleConfig) {
    if (is_string($moduleConfig)) {
        // Local module
        $this->modules[] = new $moduleConfig();
    } elseif (is_array($moduleConfig) && $moduleConfig['type'] === 'remote') {
        // Remote module
        $this->modules[] = new RemoteModule(
            $moduleConfig,
            $this->container->get(ClientInterface::class),
            $this->container->get(RequestFactoryInterface::class)
        );
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
3. Update configuration
4. Repeat for other modules

### 3. Independent Scaling

Scale microservices independently:
- High-traffic modules get more resources
- Low-traffic modules use fewer resources
- Database connections are isolated per service

### 4. Technology Flexibility

Each microservice can:
- Use different PHP versions
- Use different databases
- Deploy independently
- Have its own CI/CD pipeline

## Best Practices

### 1. Service Discovery

Use environment variables or service discovery:

```php
'base_url' => getenv('PRODUCT_SERVICE_URL') 
    ?: 'http://product-service.' . getenv('KUBERNETES_NAMESPACE') . '.svc.cluster.local',
```

### 2. Health Checks

Implement health check endpoints in microservices:

```php
$app->get('/health', function ($request, $response) {
    return $response->withJson(['status' => 'ok']);
});
```

### 3. Error Handling

Handle microservice failures gracefully:

```php
try {
    $proxyResponse = $this->httpClient->sendRequest($proxyRequest);
} catch (\Exception $e) {
    // Log error
    // Return appropriate error response
    return $response->withStatus(503)
        ->withJson(['error' => 'Service temporarily unavailable']);
}
```

### 4. Timeout Configuration

Configure appropriate timeouts:

```php
$httpClient = new \GuzzleHttp\Client([
    'timeout' => 30,
    'connect_timeout' => 5,
]);
```

### 5. Monitoring

Monitor microservice communication:
- Log all proxy requests
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
3. Update main application configuration
4. Test integration

### Phase 3: Optimization

1. Optimize microservice performance
2. Implement caching if needed
3. Add monitoring and logging
4. Scale as needed

## Example: Complete Setup

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
            'type' => 'remote',
            'name' => 'ProductCatalog',
            'base_url' => getenv('PRODUCT_SERVICE_URL'),
            'routes_prefix' => '/api/products',
        ],
        [
            'type' => 'remote',
            'name' => 'OrderManagement',
            'base_url' => getenv('ORDER_SERVICE_URL'),
            'routes_prefix' => '/api/orders',
        ],
    ],
];
```

### Product Service (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        \Metamorphose\Modules\ProductCatalog\Module::class,
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
      - PRODUCT_SERVICE_URL=http://product-service:8000
      - ORDER_SERVICE_URL=http://order-service:8000
  
  product-service:
    build: .
    ports:
      - "8001:8000"
    environment:
      - APP_ENV=production
  
  order-service:
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

### Connection Refused

- Check microservice is running
- Verify base_url is correct
- Check network connectivity
- Review firewall rules

### Context Not Preserved

- Ensure headers are forwarded
- Check middleware order
- Verify proxy implementation
- Review request/response handling

## Next Steps

- Read about [Modules](modules.md) for module development
- Learn about [Architecture](architecture.md) for system design
- Check [Contexts](contexts.md) for context management
```

```markdown:docs/pt/microservices.md
# Microserviços e Módulos Remotos

O Metamorphose Framework foi projetado para suportar tanto arquiteturas monolíticas quanto de microserviços. Você pode facilmente extrair módulos para microserviços separados sem alterar o código do módulo em si.

## Visão Geral

O framework permite que você:
- Execute todos os módulos em uma única aplicação monolítica
- Extraia módulos específicos para rodar como microserviços separados
- Misture módulos locais e remotos de forma transparente
- Migre módulos gradualmente de monólito para microserviços

## Arquitetura

### Modo Monolítico (Padrão)

Todos os módulos rodam no mesmo processo:

```
┌─────────────────────────────────┐
│     Aplicação Monolítica        │
│  ┌──────────┐  ┌──────────┐   │
│  │ Módulo A │  │ Módulo B │   │
│  └──────────┘  └──────────┘   │
│  ┌──────────┐  ┌──────────┐   │
│  │ Módulo C │  │ Módulo D │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

### Modo Microserviços

Módulos podem rodar como serviços separados:

```
┌─────────────────────────────────┐
│     Aplicação Principal         │
│  ┌──────────┐  ┌──────────┐   │
│  │ Módulo A │  │ Módulo B │   │
│  └──────────┘  └──────────┘   │
│         │              │        │
│         ▼              ▼        │
│  ┌──────────┐  ┌──────────┐   │
│  │ Product   │  │ Order    │   │
│  │ Service   │  │ Service  │   │
│  │ (Remoto)  │  │ (Remoto) │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

## Configuração

### Passo 1: Configurar Módulos Remotos

Edite `config/modules.php` para definir módulos remotos:

```php
<?php

return [
    'enabled' => [
        // Módulos locais (rodam no monólito)
        \Metamorphose\Modules\Example\Module::class,
        \Metamorphose\Modules\Auth\Module::class,
        
        // Módulos remotos (microserviços)
        [
            'type' => 'remote',
            'name' => 'ProductCatalog',
            'base_url' => getenv('PRODUCT_SERVICE_URL') ?: 'http://product-service:8000',
            'routes_prefix' => '/products', // Prefixo de rota opcional
        ],
        [
            'type' => 'remote',
            'name' => 'OrderManagement',
            'base_url' => getenv('ORDER_SERVICE_URL') ?: 'http://order-service:8000',
            'routes_prefix' => '/orders',
        ],
    ],
];
```

### Passo 2: Variáveis de Ambiente

Configure variáveis de ambiente para URLs dos microserviços:

```bash
# .env ou configuração de ambiente
PRODUCT_SERVICE_URL=http://product-service:8000
ORDER_SERVICE_URL=http://order-service:8000
```

## Criando um Microserviço

### Passo 1: Criar Entry Point do Microserviço

Crie `public/product-service.php` (ou faça deploy em servidor separado):

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);

// Carregar APENAS o módulo ProductCatalog
$loader = new \Metamorphose\Kernel\Module\ModuleLoader(
    $container,
    $app,
    [\Metamorphose\Modules\ProductCatalog\Module::class]
);
$loader->load();

$app->run();
```

### Passo 2: Configurar Microserviço

Crie `config/modules.php` no microserviço:

```php
<?php

return [
    'enabled' => [
        // Apenas este módulo roda neste microserviço
        \Metamorphose\Modules\ProductCatalog\Module::class,
    ],
];
```

### Passo 3: Fazer Deploy do Microserviço

A estrutura do microserviço:

```
product-service/
├── app/
│   └── Modules/
│       └── ProductCatalog/  # Apenas este módulo
├── config/
│   ├── app.php
│   ├── database.php
│   ├── log.php
│   └── modules.php          # Apenas ProductCatalog habilitado
├── public/
│   └── index.php             # Entry point do microserviço
└── vendor/
```

## Como Funciona

### Fluxo de Requisição

1. **Requisição chega** na aplicação principal
2. **Rota corresponde** ao prefixo do módulo remoto (ex: `/products/*`)
3. **Middleware proxy** encaminha requisição para o microserviço
4. **Headers de contexto** (`X-Tenant-ID`, `X-Unit-ID`) são preservados
5. **Resposta** é retornada ao cliente

### Preservação de Contexto

Todas as informações de contexto são automaticamente encaminhadas:

- `X-Tenant-ID`: Identificador do tenant
- `X-Unit-ID`: Identificador da unit
- `X-Tenant-Code`: Código do tenant
- `X-Unit-Code`: Código da unit
- `Authorization`: Tokens de autenticação
- Headers customizados

## Detalhes de Implementação

### Classe RemoteModule

O framework inclui uma classe `RemoteModule` que gerencia o proxy:

```php
<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Slim\App;

class RemoteModule implements ModuleInterface
{
    private string $name;
    private string $baseUrl;
    private ?string $routesPrefix;
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;

    public function __construct(
        array $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory
    ) {
        $this->name = $config['name'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->routesPrefix = $config['routes_prefix'] ?? null;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    public function register(ContainerInterface $container): void
    {
        // Nenhum serviço local para registrar
    }

    public function boot(): void
    {
        // Nenhuma inicialização local necessária
    }

    public function routes(App $app): void
    {
        $prefix = $this->routesPrefix ?? '';
        
        // Proxy para todas as rotas do microserviço
        $app->any($prefix . '/{path:.*}', function ($request, $response, $args) {
            // Encaminhar requisição para microserviço
            // Preservar todos os headers e contexto
            // Retornar resposta
        });
    }
}
```

### Melhoria do ModuleLoader

O `ModuleLoader` detecta módulos remotos:

```php
foreach ($moduleConfigs as $moduleConfig) {
    if (is_string($moduleConfig)) {
        // Módulo local
        $this->modules[] = new $moduleConfig();
    } elseif (is_array($moduleConfig) && $moduleConfig['type'] === 'remote') {
        // Módulo remoto
        $this->modules[] = new RemoteModule(
            $moduleConfig,
            $this->container->get(ClientInterface::class),
            $this->container->get(RequestFactoryInterface::class)
        );
    }
}
```

## Benefícios

### 1. Transparência de Código

O código do módulo não muda. O mesmo módulo funciona em:
- Modo monolítico
- Modo microserviço
- Modo misto

### 2. Migração Gradual

Migre módulos um de cada vez:
1. Comece com todos os módulos no monólito
2. Extraia um módulo para microserviço
3. Atualize configuração
4. Repita para outros módulos

### 3. Escalabilidade Independente

Escale microserviços independentemente:
- Módulos de alto tráfego recebem mais recursos
- Módulos de baixo tráfego usam menos recursos
- Conexões de banco de dados são isoladas por serviço

### 4. Flexibilidade Tecnológica

Cada microserviço pode:
- Usar versões diferentes de PHP
- Usar bancos de dados diferentes
- Fazer deploy independentemente
- Ter seu próprio pipeline CI/CD

## Melhores Práticas

### 1. Service Discovery

Use variáveis de ambiente ou service discovery:

```php
'base_url' => getenv('PRODUCT_SERVICE_URL') 
    ?: 'http://product-service.' . getenv('KUBERNETES_NAMESPACE') . '.svc.cluster.local',
```

### 2. Health Checks

Implemente endpoints de health check nos microserviços:

```php
$app->get('/health', function ($request, $response) {
    return $response->withJson(['status' => 'ok']);
});
```

### 3. Tratamento de Erros

Trate falhas de microserviços graciosamente:

```php
try {
    $proxyResponse = $this->httpClient->sendRequest($proxyRequest);
} catch (\Exception $e) {
    // Logar erro
    // Retornar resposta de erro apropriada
    return $response->withStatus(503)
        ->withJson(['error' => 'Serviço temporariamente indisponível']);
}
```

### 4. Configuração de Timeout

Configure timeouts apropriados:

```php
$httpClient = new \GuzzleHttp\Client([
    'timeout' => 30,
    'connect_timeout' => 5,
]);
```

### 5. Monitoramento

Monitore comunicação entre microserviços:
- Logar todas as requisições proxy
- Rastrear tempos de resposta
- Alertar sobre falhas
- Monitorar saúde dos serviços

## Estratégia de Migração

### Fase 1: Preparação

1. Identificar módulos para extrair
2. Garantir que módulos são autocontidos
3. Verificar que não há dependências diretas entre módulos
4. Testar módulos independentemente

### Fase 2: Extração

1. Criar entry point do microserviço
2. Fazer deploy do microserviço
3. Atualizar configuração da aplicação principal
4. Testar integração

### Fase 3: Otimização

1. Otimizar performance do microserviço
2. Implementar cache se necessário
3. Adicionar monitoramento e logging
4. Escalar conforme necessário

## Exemplo: Configuração Completa

### Aplicação Principal (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        // Módulos locais
        \Metamorphose\Modules\Auth\Module::class,
        \Metamorphose\Modules\UserManagement\Module::class,
        
        // Microserviços remotos
        [
            'type' => 'remote',
            'name' => 'ProductCatalog',
            'base_url' => getenv('PRODUCT_SERVICE_URL'),
            'routes_prefix' => '/api/products',
        ],
        [
            'type' => 'remote',
            'name' => 'OrderManagement',
            'base_url' => getenv('ORDER_SERVICE_URL'),
            'routes_prefix' => '/api/orders',
        ],
    ],
];
```

### Serviço de Produtos (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        \Metamorphose\Modules\ProductCatalog\Module::class,
    ],
];
```

### Exemplo Docker Compose

```yaml
version: '3.8'

services:
  main-app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - PRODUCT_SERVICE_URL=http://product-service:8000
      - ORDER_SERVICE_URL=http://order-service:8000
  
  product-service:
    build: .
    ports:
      - "8001:8000"
    environment:
      - APP_ENV=production
  
  order-service:
    build: .
    ports:
      - "8002:8000"
    environment:
      - APP_ENV=production
```

## Solução de Problemas

### Módulo Não Encontrado

- Verificar se a classe do módulo existe
- Verificar namespace e autoloading
- Garantir que módulo está no diretório correto

### Conexão Recusada

- Verificar se microserviço está rodando
- Verificar se base_url está correto
- Verificar conectividade de rede
- Revisar regras de firewall

### Contexto Não Preservado

- Garantir que headers são encaminhados
- Verificar ordem dos middlewares
- Verificar implementação do proxy
- Revisar tratamento de requisição/resposta

## Próximos Passos

- Leia sobre [Módulos](modules.md) para desenvolvimento de módulos
- Aprenda sobre [Arquitetura](architecture.md) para design de sistema
- Verifique [Contextos](contexts.md) para gerenciamento de contexto
```

Agora atualizando os READMEs para incluir links para essa nova documentação:

```markdown:docs/en/README.md
# Metamorphose Framework - Documentation

Metamorphose Framework is a modular, multi-tenant PHP application kernel based on Slim Framework.

## Table of Contents

1. [Installation](installation.md)
2. [Getting Started](getting-started.md)
3. [Architecture](architecture.md)
4. [Modules](modules.md)
5. [CLI Commands](cli.md)
6. [Contexts](contexts.md)
7. [Database](database.md)
8. [Logging](logging.md)
9. [Permissions](permissions.md)
10. [Microservices](microservices.md)

## Overview

Metamorphose Framework is designed to be:

- **Modular**: Plug-and-play modules that can be easily added or removed
- **Multi-tenant**: Supports core, tenant, and unit scopes
- **Context-aware**: Explicit contexts for tenant, unit, and request
- **Runtime-agnostic**: Works with PHP-FPM, Swoole, and FrankenPHP
- **PSR-compliant**: Follows PSR-4, PSR-7, PSR-11, and PSR-15 standards
- **Explicit**: No hidden magic, all code is explicit and readable
- **Microservices-ready**: Easily extract modules to separate microservices

## Key Features

- Modular architecture with pluggable modules
- Multi-tenant support (core, tenant, unit)
- Explicit context management
- Flexible database connections per scope
- Structured logging with HTTP middleware
- Multi-scope permission system
- Built-in CLI for module creation and migrations
- No global mutable state
- Compatible with persistent runtimes
- **Microservices support**: Run modules as separate services

## Requirements

- PHP >= 8.1
- Composer
- MySQL/MariaDB (or compatible database)

## Quick Start

```bash
# Install dependencies
composer install

# Create a new module
php bin/metamorphose module:make MyModule

# Run migrations
php bin/metamorphose migrate --scope=core
```

## Documentation Structure

Each section of the documentation covers a specific aspect of the framework:

- **Installation**: How to set up the framework
- **Getting Started**: Your first steps with the framework
- **Architecture**: Understanding the framework's architecture
- **Modules**: Creating and managing modules
- **CLI Commands**: Using the command-line interface
- **Contexts**: Working with tenant, unit, and request contexts
- **Database**: Database connections and migrations
- **Logging**: Configuring and using the logging system
- **Permissions**: Implementing permission checks
- **Microservices**: Extracting modules to separate microservices
```

```markdown:docs/pt/README.md
# Metamorphose Framework - Documentação

Metamorphose Framework é um kernel de aplicação PHP modular e multi-tenant baseado em Slim Framework.

## Índice

1. [Instalação](installation.md)
2. [Primeiros Passos](getting-started.md)
3. [Arquitetura](architecture.md)
4. [Módulos](modules.md)
5. [Comandos CLI](cli.md)
6. [Contextos](contexts.md)
7. [Banco de Dados](database.md)
8. [Logs](logging.md)
9. [Permissões](permissions.md)
10. [Microserviços](microservices.md)

## Visão Geral

O Metamorphose Framework foi projetado para ser:

- **Modular**: Módulos plug-and-play que podem ser facilmente adicionados ou removidos
- **Multi-tenant**: Suporta escopos core, tenant e unit
- **Orientado a contexto**: Contextos explícitos para tenant, unit e request
- **Agnóstico de runtime**: Funciona com PHP-FPM, Swoole e FrankenPHP
- **Compatível com PSR**: Segue os padrões PSR-4, PSR-7, PSR-11 e PSR-15
- **Explícito**: Sem mágica oculta, todo código é explícito e legível
- **Pronto para microserviços**: Extraia facilmente módulos para microserviços separados

## Principais Recursos

- Arquitetura modular com módulos plugáveis
- Suporte multi-tenant (core, tenant, unit)
- Gerenciamento explícito de contextos
- Conexões de banco de dados flexíveis por escopo
- Sistema de logs estruturado com middleware HTTP
- Sistema de permissões multi-escopo
- CLI integrado para criação de módulos e migrações
- Sem estado global mutável
- Compatível com runtimes persistentes
- **Suporte a microserviços**: Execute módulos como serviços separados

## Requisitos

- PHP >= 8.1
- Composer
- MySQL/MariaDB (ou banco de dados compatível)

## Início Rápido

```bash
# Instalar dependências
composer install

# Criar um novo módulo
php bin/metamorphose module:make MeuModulo

# Executar migrações
php bin/metamorphose migrate --scope=core
```

## Estrutura da Documentação

Cada seção da documentação cobre um aspecto específico do framework:

- **Instalação**: Como configurar o framework
- **Primeiros Passos**: Seus primeiros passos com o framework
- **Arquitetura**: Entendendo a arquitetura do framework
- **Módulos**: Criando e gerenciando módulos
- **Comandos CLI**: Usando a interface de linha de comando
- **Contextos**: Trabalhando com contextos de tenant, unit e request
- **Banco de Dados**: Conexões de banco de dados e migrações
- **Logs**: Configurando e usando o sistema de logs
- **Permissões**: Implementando verificações de permissão
- **Microserviços**: Extraindo módulos para microserviços separados
```

Você está em modo de leitura. Para aplicar essas mudanças, alterne para o modo de edição ou copie os arquivos acima para:

1. `docs/en/microservices.md` - Documentação em inglês
2. `docs/pt/microservices.md` - Documentação em português
3. Atualizar `docs/en/README.md` e `docs/pt/README.md` com os links

A documentação cobre:
- Visão geral da arquitetura
- Como configurar módulos remotos
- Como criar um microserviço
- Detalhes de implementação
- Benefícios e melhores práticas
- Estratégia de migração
- Exemplos práticos
- Solução de problemas

Quer que eu aplique essas mudanças?
```

